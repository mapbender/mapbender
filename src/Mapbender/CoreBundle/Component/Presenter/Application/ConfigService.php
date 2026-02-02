<?php

namespace Mapbender\CoreBundle\Component\Presenter\Application;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\Event\ApplicationConfigEvent;
use Mapbender\Component\Event\ApplicationEvent;
use Mapbender\CoreBundle\Component\ElementBase\ValidatableConfigurationInterface;
use Mapbender\CoreBundle\Component\ElementBase\ValidationFailedException;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceAssignment;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service that generates the frontend-facing configuration for a Mapbender application.
 *
 *
 * Instance registerd in container as mapbender.presenter.application.config.service, see services.xml
 */
class ConfigService
{
    /** @var string */
    protected $assetBaseUrl;


    public function __construct(protected EventDispatcherInterface $eventDispatcher,
                                protected ElementFilter            $elementFilter,
                                protected TypeDirectoryService     $sourceTypeDirectory,
                                protected EntityManagerInterface   $em,
                                protected UrlProcessor             $urlProcessor,
                                protected UrlGeneratorInterface    $router,
                                protected PackageInterface         $baseUrlPackage,
                                protected TranslatorInterface      $translator,
                                protected bool                     $debug)
    {
        $this->assetBaseUrl = $baseUrlPackage->getUrl('');
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
        $this->preloadSources($entity);
        $configuration['layersets'] = $this->getLayerSetConfigs($entity);

        $evt = new ApplicationConfigEvent($entity, $configuration);
        $this->eventDispatcher->dispatch($evt, ApplicationConfigEvent::EVTNAME_AFTER_CONFIG);
        $configuration = $evt->getConfiguration();

        return $configuration;
    }

    public function getBaseConfiguration(Application $entity)
    {
        return array(
            'title' => $entity->getTitle(),
            'urls' => $this->getUrls($entity),
            'publicOptions' => $entity->getPublicOptions(),
            'slug' => $entity->getSlug(),
            'debug' => $this->debug,
            'mapEngineCode' => 'current',
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
        $config = array('slug' => $entity->getSlug());

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
     * @return array[]
     */
    protected function getSourceInstanceConfigs(Layerset $layerset): array
    {
        $configs = array();
        foreach ($this->filterActiveSourceInstanceAssignments($layerset) as $assignment) {
            $configGenerator = $this->sourceTypeDirectory->getConfigGenerator($assignment->getInstance());
            if (!$configGenerator->isInstanceEnabled($assignment->getInstance())) {
                continue;
            }
            $configs[] = $configGenerator->getConfiguration($layerset->getApplication(), $assignment->getInstance());
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
                    if ($handler instanceof ValidatableConfigurationInterface) {
                        try {
                            $handler::validate($values['configuration'], null, $this->translator);
                        } catch (ValidationFailedException $e) {
                            $values['errors'] = [$e->getMessage()];
                        }
                    }
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
     * Preload all sources used in this application to avoid separate requests to database caused
     * by doctrine resolving references one by one
     */
    protected function preloadSources(Application $application): void
    {
        // Preloading sources is only necessary for DB-based applications
        if ($application->getSource() !== Application::SOURCE_DB) return;

        $layersets = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Layerset::class, 'l')
            ->where('l.application = :application')
            ->setParameter('application', $application->getId())
            ->getQuery()
            ->getResult()
        ;

        /** @var SourceInstance[] $sources */
        $sources = $this->em->createQueryBuilder()
            ->select('s')
            ->from(SourceInstance::class, 's')
            ->where('s.layerset IN (:layersets)')
            ->setParameter('layersets', $layersets)
            ->getQuery()
            ->getResult()
        ;

        /** @var SourceInstance[] $sourcesFree */
        $sourcesFree = $this->em->createQueryBuilder()
            ->select('s')
            ->from(ReusableSourceInstanceAssignment::class, 'a')
            ->where('a.layerset IN (:layersets)')
            ->setParameter('layersets', $layersets)
            ->join(SourceInstance::class, 's', 'WITH', 's.id = a.instance')
            ->getQuery()
            ->getResult()
        ;

        // iterate over sources and sourcesFree
        $preloadConfig = [];
        foreach (array_merge($sources, $sourcesFree) as $source) {
            $type = $source->getType();
            if (!array_key_exists($type, $preloadConfig)) {
                $preloadConfig[$type] = [];
            }
            $preloadConfig[$type][] = $source;
        }

        foreach ($preloadConfig as $type => $sources) {
            $configGenerator = $this->sourceTypeDirectory->getSource($type)->getConfigGenerator();
            $configGenerator->preload($sources);
        }

    }
}
