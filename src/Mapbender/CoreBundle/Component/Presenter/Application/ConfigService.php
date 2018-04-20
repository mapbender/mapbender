<?php

namespace Mapbender\CoreBundle\Component\Presenter\Application;

use Mapbender\CoreBundle\Component\Cache\ApplicationDataService;
use Mapbender\CoreBundle\Component\Element as Element;
use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\DependencyInjection\Compiler\RegisterWmsSourceServicePass;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Mapbender\CoreBundle\Component\Presenter\ApplicationService;

/**
 * Service that generates the frontend-facing configuration for a Mapbender application
 *
 * Instance registerd in container as mapbender.presenter.application.config.service, see services.xml
 *
 * Handlers for polymorphic source instance types pluggable and extensible by injecting method calls to
 * * @see ConfigService::setDefaultSourceService
 * * @see ConfigService::addSourceService
 *
 * This should be done in a DI compiler pass (extending service definition via XML / YAML does not work across bundles)
 * @see RegisterWmsSourceServicePass for a working example
 */
class ConfigService
{
    /** @var ContainerInterface */
    protected $container;
    /** @var ApplicationService */
    protected $basePresenter;
    /** @var SourceService[] */
    protected $sourceServices = array();
    /** @var SourceService|null */
    protected $defaultSourceService;
    /** @var ApplicationDataService */
    protected $cacheService;



    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->basePresenter = $container->get('mapbender.presenter.application.service');
        $this->cacheService = $container->get('mapbender.presenter.application.cache');
    }

    /**
     * @param Application $entity
     * @return mixed[]
     */
    public function getConfiguration(Application $entity)
    {
        $activeElements = $this->basePresenter->getActiveElements($entity);
        $configuration = array(
            'application' => $this->getBaseConfiguration($entity),
            'elements'    => $this->getElementConfiguration($activeElements),
        );
        $configuration += $this->getLayerSetConfiguration($entity);

        // Let (active, visible) Elements update the Application config
        // This is useful for BaseSourceSwitcher, SuggestMap, potentially many more, that influence the initially
        // visible state of the frontend.
        $configBeforeElement = $configAfterElements = $configuration;
        foreach ($activeElements as $elementComponent) {
            $configAfterElements = $configBeforeElement = $elementComponent->updateAppConfig($configBeforeElement);
        }
        return $configAfterElements;
    }

    public function getBaseConfiguration(Application $entity)
    {
        return array(
            'title'         => $entity->getTitle(),
            'urls'          => $this->getUrls($entity),
            'publicOptions' => $entity->getPublicOptions(),
            'slug'          => $entity->getSlug(),
        );
    }

    /**
     * Get runtime URLs
     * Hack to get proper urls when embedded in drupal
     * @todo: Drupal hacks should reside in DrupalIntegrationBundle
     *
     * @param Application $entity
     * @return array
     */
    public function getUrls(Application $entity)
    {
        $config        = array('slug' => $entity->getSlug());
        $router        = $this->getRouter();
        $searchSubject = 'mapbender';
        $drupal_mark   = function_exists('mapbender_menu') ? '?q=mapbender' : $searchSubject;

        $urls = array(
            'base'     => $this->container->get('request')->getBaseUrl(),
            'asset'    => $this->container->get('templating.helper.assets')->getUrl(null),
            'element'  => $router->generate('mapbender_core_application_element', $config),
            'trans'    => $router->generate('mapbender_core_translation_trans'),
            'proxy'    => $router->generate('owsproxy3_core_owsproxy_entrypoint'),
            'metadata' => $router->generate('mapbender_core_application_metadata', $config),
            'config'   => $router->generate('mapbender_core_application_configuration', $config));

        if ($searchSubject !== $drupal_mark) {
            foreach ($urls as $k => $v) {
                if ($k == "asset") {
                    continue;
                }
                $urls[$k] = str_replace($searchSubject, $drupal_mark, $v);
            }
        }

        return $urls;
    }

    /**
     * Returns layerset config. This is actually already an array with two subarrays 'layersets' (actual config)
     * and 'layersetmap' (layer titles).
     *
     * @param Application $entity
     * @return array[]
     */
    public function getLayerSetConfiguration(Application $entity)
    {
        $configs = array();
        $titles = array();
        foreach ($entity->getLayersets() as $layerSet) {
            $layerId       = '' . $layerSet->getId();
            $layerSetTitle = $layerSet->getTitle() ? $layerSet->getTitle() : $layerId;
            $layerSets     = array();

            foreach ($this->filterActiveSourceInstances($layerSet) as $layer) {
                $sourceService = $this->getSourceService($layer);
                if (!$sourceService) {
                    // @todo: throw?
                    continue;
                }
                $conf = $sourceService->getConfiguration($layer);
                if (!$conf) {
                    // @todo: throw?
                    continue;
                }
                $layerSets[] = array(
                    $layer->getId() => $conf,
                );
            }

            $configs[$layerId] = $layerSets;
            $titles[$layerId] = $layerSetTitle;
        }
        return array(
            'layersets' => $configs,
            'layersetmap' => $titles,
        );
    }

    /**
     * @param Element[] $elements Element Components
     * @return mixed[]
     */
    public static function getElementConfiguration($elements)
    {
        $elementConfig = array();
        foreach ($elements as $element) {
            $elementConfig[$element->getId()] = array(
                'init'          => $element->getWidgetName(),
                'configuration' => $element->getPublicConfiguration());
        }
        return $elementConfig;
    }

    /**
     * Get the appropriate service to deal with the given SourceInstance child class.
     * Tries first to get a match based on type (e.g. 'wms'). If no specific handling service is configured, fall
     * back to an internal default (which currently happens to be the same as for 'wms').
     *
     * To extend the list of available source instance handling services, @see getSourceServices
     *
     * @param SourceInstance $sourceInstance
     * @return SourceService|null
     */
    public function getSourceService(SourceInstance $sourceInstance)
    {
        $key = strtolower($sourceInstance->getType());
        $service = ArrayUtil::getDefault($this->sourceServices, $key, null);
        if (!$service) {
            $service = $this->defaultSourceService;
            if ($service) {
                $message = 'Using default source service for source instance type ' . print_r($key, true);
                $this->getLogger()->warning(__CLASS__ . ": {$message}");
            }
        }
        if (!$service) {
            $message = 'No config generator available for source instance type ' . print_r($key, true);
            throw new \RuntimeException($message);
        }
        // lazy-get services plugged as service id strings; this avoids circular dependencies in container
        // building extensions
        if (is_string($service)) {
            $service = $this->container->get($service);
            // NOTE: The service id may have come from $this->defaultSourceService.
            //       This is harmless. The sourceServices array is checked first, and the worst that can happen
            //       is that we create an implicit entry for every type that uses the default service.
            $this->sourceServices[$key] = $service;
        }
        return $service;
    }

    /**
     * Adds (or replaces) the sub-service for generating configuration for specific types of source instances.
     *
     * Provided as a post-constructor customization hook. Should call from a DI compiler pass to plug in
     * support for new source types.
     *
     * @see RegisterWmsSourceServicePass for a working example
     *
     * @param string $instanceType
     * @param SourceService|string $service service instance or the service id string (for lazy evaluation)
     */
    public function addSourceService($instanceType, $service)
    {
        if (!$instanceType || !is_string($instanceType)) {
            throw new \InvalidArgumentException('Empty / non-string instanceType ' . print_r($instanceType));
        }
        $key = strtolower($instanceType);
        $service = $this->validateSourceServiceType($service, true);
        if (!$service) {
            unset($this->sourceServices[$key]);
        } else {
            $this->sourceServices[$key] = $service;
        }
    }

    /**
     * Sets (or removes) the default service for generating source instance configuration. This default
     * service is used as the fallback if the specific type ("wms") has not been explicitly wired to a
     * specialized service via @see addSourceService
     *
     * The default is set via services.xml. This should not require compiler pass DI, though that's possible, too.
     *
     * @param SourceService|string $service
     */
    public function setDefaultSourceService($service = null)
    {
        $this->defaultSourceService = $this->validateSourceServiceType($service, true);
    }

    /**
     * Validates / restricts given source service reference to a usable type. Allowed here:
     * * string (interpreted as a service id for lazy evaluation)
     * * SourceService instance
     * * falsy ONLY IF $allowEmpty = false (will be cast to null), otherwise throws
     * Returns the input, falsy converted to null.
     *
     * @param mixed $serviceOrId
     * @param boolean $allowEmpty
     * @return SourceService|string|null
     * @throws \InvalidArgumentException if unsupported input type, or input empty and $allowEmpty false
     */
    protected function validateSourceServiceType($serviceOrId, $allowEmpty)
    {
        if (!$serviceOrId) {
            if (!$allowEmpty) {
                throw new \InvalidArgumentException("Empty source service reference not allowed in this context");
            }
            return null;
        } else {
            if (!is_string($serviceOrId) && !($serviceOrId instanceof SourceService)) {
                $type = is_object($serviceOrId) ? get_class($serviceOrId) : gettype($serviceOrId);
                throw new \InvalidArgumentException("Unsupported type {$type}, must be string or SourceService");
            }
            return $serviceOrId;
        }
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function getRouter()
    {
        /** @var UrlGeneratorInterface $router */
        $router = $this->container->get('router');
        return $router;
    }

    /**
     * Extracts active source instances from given Layerset entity.
     *
     * @param Layerset $entity
     * @return SourceInstance[]
     */
    protected static function filterActiveSourceInstances(Layerset $entity)
    {
        $isYamlApp = $entity->getApplication()->isYamlBased();
        $activeInstances = array();
        foreach ($entity->getInstances() as $instance) {
            if ($isYamlApp || $instance->getEnabled()) {
                $activeInstances[] = $instance;
            }
        }
        return $activeInstances;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get('logger');
        return $logger;
    }

    /**
     * @return ApplicationDataService
     */
    public function getCacheService()
    {
        /** @var ApplicationDataService $cacheService */
        $cacheService = $this->container->get('mapbender.presenter.application.cache');
        return $cacheService;
    }
}
