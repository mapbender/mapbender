<?php

namespace Mapbender\CoreBundle\Component\Presenter\Application;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Security\Permission\PermissionManager;
use FOM\UserBundle\Security\Permission\ResourceDomainSourceInstance;
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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service that generates the frontend-facing configuration for a Mapbender application.
 *
 *
 * Instance registered in container as mapbender.presenter.application.config.service, see services.xml
 */
class ConfigService
{
    protected string $assetBaseUrl;


    public function __construct(protected EventDispatcherInterface $eventDispatcher,
                                protected ElementFilter            $elementFilter,
                                protected TypeDirectoryService     $sourceTypeDirectory,
                                protected EntityManagerInterface   $em,
                                protected UrlProcessor             $urlProcessor,
                                protected UrlGeneratorInterface    $router,
                                protected PackageInterface         $baseUrlPackage,
                                protected TranslatorInterface      $translator,
                                protected PermissionManager        $permissionManager,
                                protected Security                 $security,
                                protected bool                     $debug)
    {
        $this->assetBaseUrl = $baseUrlPackage->getUrl('');
    }

    public function getConfiguration(Application $entity): array
    {
        $activeElements = $this->elementFilter->prepareFrontend($entity->getElements(), true, false);

        $this->eventDispatcher->dispatch(new ApplicationEvent($entity), ApplicationEvent::EVTNAME_BEFORE_CONFIG);

        $configuration = array(
            'application' => $this->getBaseConfiguration($entity),
            'elements' => $this->getElementConfiguration($activeElements),
        );
        $this->preloadSources($entity);
        $configuration['layersets'] = $this->getLayerSetConfigs($entity);

        $evt = new ApplicationConfigEvent($entity, $configuration);
        $this->eventDispatcher->dispatch($evt, ApplicationConfigEvent::EVTNAME_AFTER_CONFIG);
        return $evt->getConfiguration();
    }

    public function getBaseConfiguration(Application $entity): array
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
     */
    public function getUrls(Application $entity): array
    {
        $config = array('slug' => $entity->getSlug());

        return array(
            'base' => $this->router->getContext()->getBaseUrl(),
            'asset' => $this->assetBaseUrl,
            'element' => $this->router->generate('mapbender_core_application_element', $config),
            'proxy' => $this->urlProcessor->getProxyBaseUrl(),
            'config' => $this->router->generate('mapbender_core_application_configuration', $config),
        );
    }

    /**
     * @return array[]
     */
    public function getLayerSetConfigs(Application $entity): array
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
        return array_filter($configs, fn($config) => !empty($config['instances']));
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
            $configs[] = $configGenerator->getConfiguration($assignment->getInstance());
        }
        return $configs;
    }

    /**
     * @param Entity\Element[] $elements
     */
    protected function getElementConfiguration(array $elements): array
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
                } catch (ElementErrorException) {
                    // for frontend presentation, incomplete / invalid elements are silently suppressed
                    // => do nothing
                    continue;
                }
                $elementConfig[$element->getId()] = $values;
            }
        }
        return $elementConfig;
    }

    protected function isSourceInstanceViewGranted(SourceInstance|ReusableSourceInstanceAssignment $instance): bool
    {
        // if no permissions are defined for the instance, everyone with access to the application can access the source
        if (!$this->permissionManager->hasPermissionsDefined($instance)) return true;

        return $this->security->isGranted(ResourceDomainSourceInstance::ACTION_VIEW, $instance);
    }

    /**
     * Extracts active and source instances from given Layerset entity where the current used has permission to view.
     * @return SourceInstanceAssignment[]
     */
    protected function filterActiveSourceInstanceAssignments(Layerset $entity): array
    {
        $isYamlApp = $entity->getApplication()->isYamlBased();
        $active = [];

        foreach ($entity->getCombinedInstanceAssignments() as $assignment) {
            if (($isYamlApp || $assignment->getEnabled()) && $this->isSourceInstanceViewGranted($assignment)) {
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
