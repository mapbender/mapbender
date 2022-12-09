<?php

namespace Mapbender\CoreBundle\Component\Presenter\Application;

use Mapbender\Component\Event\ApplicationConfigEvent;
use Mapbender\Component\Event\ApplicationEvent;
use Mapbender\Component\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service that generates the frontend-facing configuration for a Mapbender application.
 *
 *
 * Instance registerd in container as mapbender.presenter.application.config.service, see services.xml
 */
class ConfigService
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;
    /** @var UrlProcessor */
    protected $urlProcessor;

    /** @var UrlGeneratorInterface */
    protected $router;
    /** @var bool */
    protected $debug;
    /** @var string */
    protected $assetBaseUrl;


    public function __construct(EventDispatcherInterface $eventDispatcher,
                                ElementFilter $elementFilter,
                                TypeDirectoryService $sourceTypeDirectory,
                                UrlProcessor $urlProcessor,
                                UrlGeneratorInterface $router,
                                PackageInterface $baseUrlPackage,
                                $debug)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->elementFilter = $elementFilter;
        $this->sourceTypeDirectory = $sourceTypeDirectory;
        $this->urlProcessor = $urlProcessor;
        $this->router = $router;
        $this->assetBaseUrl = $baseUrlPackage->getUrl(null);
        $this->debug = $debug;
    }

    /**
     * @param Application $entity
     * @return mixed[]
     */
    public function getConfiguration(Application $entity)
    {
        $activeElements = $this->elementFilter->prepareFrontend($entity->getElements(), true, false);

        $this->eventDispatcher->dispatch(new ApplicationEvent($entity), ApplicationEvent::EVTNAME_BEFORE_CONFIG);

        $configuration = array(
            'application' => $this->getBaseConfiguration($entity),
            'elements'    => $this->getElementConfiguration($activeElements),
        );
        $configuration['layersets'] = $this->getLayerSetConfigs($entity);

        $evt = new ApplicationConfigEvent($entity, $configuration);
        $this->eventDispatcher->dispatch($evt, ApplicationConfigEvent::EVTNAME_AFTER_CONFIG);
        $configuration = $evt->getConfiguration();

        return $configuration;
    }

    public function getBaseConfiguration(Application $entity)
    {
        return array(
            'title'         => $entity->getTitle(),
            'urls'          => $this->getUrls($entity),
            'publicOptions' => $entity->getPublicOptions(),
            'slug'          => $entity->getSlug(),
            'debug' => $this->debug,
            'mapEngineCode' => $entity->getMapEngineCode(),
            'persistentView' => $entity->getPersistentView(),
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

        $urls = array(
            'base' => $this->router->getContext()->getBaseUrl(),
            'asset' => $this->assetBaseUrl,
            'element' => $this->router->generate('mapbender_core_application_element', $config),
            'proxy' => $this->urlProcessor->getProxyBaseUrl(),
            'config' => $this->router->generate('mapbender_core_application_configuration', $config),
        );

        return $urls;
    }

    /**
     * Returns layerset configs.
     *
     * @param Application $entity
     * @return array[]
     */
    public function getLayerSetConfigs(Application $entity)
    {
        $configs = array();
        foreach ($entity->getLayersets() as $layerSet) {
            $configs[] = array(
                'id' => strval($layerSet->getId()),
                'title' => $layerSet->getTitle() ?: strval($layerSet->getId()),
                'selected' => $layerSet->getSelected(),
                'instances' => $this->getSourceInstanceConfigs($layerSet),
            );
        }
        return $configs;
    }

    /**
     * @param Layerset $layerset
     * @return array[]
     */
    protected function getSourceInstanceConfigs(Layerset $layerset)
    {
        $configs = array();
        foreach ($this->filterActiveSourceInstanceAssignments($layerset) as $assignment) {
            $sourceService = $this->getSourceService($assignment->getInstance());
            if (!$sourceService->isInstanceEnabled($assignment->getInstance())) {
                continue;
            }
            $configs[] = $sourceService->getConfiguration($assignment->getInstance());
        }
        return $configs;
    }

    /**
     * @param Entity\Element[] $elements
     * @return mixed[]
     */
    protected function getElementConfiguration($elements)
    {
        $elementConfig = array();
        foreach ($elements as $element) {
            $handler = $this->elementFilter->getInventory()->getFrontendHandler($element);
            if ($handler) {
                try {
                    $values = array(
                        'init' => $handler->getWidgetName($element),
                        'configuration' => $handler->getClientConfiguration($element),
                    );
                } catch (ElementErrorException $e) {
                    // for frontend presentation, incomplete / invalid elements are silently suppressed
                    // => do nothing
                    continue;
                }
                $elementConfig[$element->getId()] = $values;
            }
        }
        return $elementConfig;
    }

    /**
     * Get the concrete service that deals with the concrete SourceInstance type.
     *
     * @param SourceInstance $sourceInstance
     * @return SourceInstanceConfigGenerator
     */
    protected function getSourceService(SourceInstance $sourceInstance)
    {
        // delegate to directory
        return $this->sourceTypeDirectory->getConfigGenerator($sourceInstance);
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
}
