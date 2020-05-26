<?php

namespace Mapbender\CoreBundle\Component\Presenter\Application;

use Mapbender\CoreBundle\Component\Cache\ApplicationDataService;
use Mapbender\CoreBundle\Component\Element as Element;
use Mapbender\CoreBundle\Component\ElementBase\BoundConfigMutator;
use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Mapbender\CoreBundle\Component\Presenter\ApplicationService;

/**
 * Service that generates the frontend-facing configuration for a Mapbender application.
 *
 *
 * Instance registerd in container as mapbender.presenter.application.config.service, see services.xml
 */
class ConfigService
{
    /** @var ContainerInterface */
    protected $container;
    /** @var ApplicationService */
    protected $basePresenter;
    /** @var ApplicationDataService */
    protected $cacheService;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;
    /** @var UrlProcessor */
    protected $urlProcessor;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->basePresenter = $container->get('mapbender.presenter.application.service');
        $this->cacheService = $container->get('mapbender.presenter.application.cache');
        $this->sourceTypeDirectory = $container->get('mapbender.source.typedirectory.service');
        $this->urlProcessor = $container->get('mapbender.source.url_processor.service');
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
            if ($elementComponent instanceof BoundConfigMutator) {
                $configAfterElements = $elementComponent->updateAppConfig($configBeforeElement);
                $configBeforeElement = $configAfterElements;
            }
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
            'debug'         => ($this->container->get('kernel')->getEnvironment() !== 'prod'),
            'mapEngineCode' => $entity->getMapEngineCode(),
        );
    }

    /**
     * Get runtime URLs
     *
     * @param Application $entity
     * @return array
     */
    public function getUrls(Application $entity)
    {
        $config        = array('slug' => $entity->getSlug());
        $router        = $this->getRouter();

        $urls = array(
            'base' => $router->getContext()->getBaseUrl(),
            'asset'    => $this->container->get('assets.packages')->getUrl(null),
            'element'  => $router->generate('mapbender_core_application_element', $config),
            'proxy'    => $this->urlProcessor->getProxyBaseUrl(),
            'metadata' => $router->generate('mapbender_core_application_metadata', $config),
            'config'   => $router->generate('mapbender_core_application_configuration', $config),
        );

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

            foreach ($this->filterActiveSourceInstanceAssignments($layerSet) as $assignment) {
                $sourceService = $this->getSourceService($assignment->getInstance());
                if (!$sourceService) {
                    // @todo: throw?
                    continue;
                }
                // @todo: move check into prefilter (get service twice?)
                if (!$sourceService->isInstanceEnabled($assignment->getInstance())) {
                    continue;
                }
                $conf = $sourceService->getConfiguration($assignment->getInstance());
                if (!$conf) {
                    // @todo: throw?
                    continue;
                }
                if ($assignment->getInstance()->getLayerset()) {
                    $assignmentId = 'bound' . $assignment->getInstance()->getId();
                } else {
                    $assignmentId = 'shared' . $assignment->getId();
                }
                // HACK: rewrite ids to fix massive JavaScript-side confusion about duplicate "source" ids
                $conf['id'] = $assignmentId;

                $layerSets[] = array(
                    $assignmentId => $conf,
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
     * Get the concrete service that deals with the concrete SourceInstance type.
     *
     * @param SourceInstance $sourceInstance
     * @return SourceService|null
     */
    protected function getSourceService(SourceInstance $sourceInstance)
    {
        // delegate to directory
        return $this->sourceTypeDirectory->getSourceService($sourceInstance);
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
     * @return SourceInstanceAssignment[]
     */
    protected static function filterActiveSourceInstanceAssignments(Layerset $entity)
    {
        $isYamlApp = $entity->getApplication()->isYamlBased();
        $active = array();
        foreach ($entity->getCombinedInstanceAssignments() as $assignment) {
            if ($isYamlApp || $assignment->getEnabled()) {
                $active[] = $assignment;
            }
        }
        return $active;
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
