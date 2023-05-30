<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\Component\SourceInstanceConfigGenerator;
use Mapbender\Component\SourceInstanceFactory;
use Mapbender\Component\SourceLoader;
use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\WmsBundle\DependencyInjection\Compiler\RegisterWmsSourceServicePass;

/**
 * Directory for available polymorphic source types (shipping by default only with WMS). Each source type is
 * expected to supply its own service that performs source-type-specific tasks such as
 * * generating frontend configuration
 * * locating the correct form type for administration (WIP)
 *
 * The directory itself is registered in container at mapbender.source.typedirectory.service
 *
 * Handlers for polymorphic source instance types pluggable and extensible by injecting method calls to
 * * @see TypeDirectoryService::registerSubtypeService
 *
 * This should be done in a DI compiler pass (extending service definition via XML / YAML does not work across bundles)
 * @see RegisterWmsSourceServicePass for a working example
 */
class TypeDirectoryService implements SourceInstanceFactory, SourceInstanceInformationInterface
{
    /** @var SourceInstanceConfigGenerator[] */
    protected $configServices = array();
    /** @var SourceInstanceFactory[] */
    protected $instanceFactories = array();
    /** @var SourceLoader[] */
    protected $loaders = array();

    /**
     * Return a mapping of type codes => displayable type labels
     * @return string[]
     */
    public function getTypeLabels()
    {
        $labelMap = array();
        foreach ($this->loaders as $loader) {
            $labelMap[$loader->getTypeCode()] = $loader->getTypeLabel();
        }
        return $labelMap;
    }

    /**
     * @param SourceInstance $sourceInstance
     * @return SourceInstanceConfigGenerator
     * @deprecated for unspecific wording; use getConfigGenerator
     */
    public function getSourceService(SourceInstance $sourceInstance)
    {
        return $this->getConfigGenerator($sourceInstance);
    }

    /**
     * Get the appropriate service to handle configuration generation
     * for the given source instance.
     *
     * @param SourceInstance $sourceInstance
     * @return SourceInstanceConfigGenerator
     */
    public function getConfigGenerator(SourceInstance $sourceInstance)
    {
        $key = strtolower($sourceInstance->getType());
        if (!array_key_exists($key, $this->configServices)) {
            $message = 'No config generator available for source instance type ' . print_r($key, true);
            throw new \RuntimeException($message);
        }
        return $this->configServices[$key];
    }

    /**
     * @param Source $source
     * @return SourceInstanceFactory
     */
    public function getInstanceFactory(Source $source)
    {
        return $this->getInstanceFactoryByType($source->getType());
    }

    /**
     * @param string $type
     * @return SourceInstanceFactory
     */
    public function getInstanceFactoryByType($type)
    {
        $key = strtolower($type);
        if (!array_key_exists($key, $this->instanceFactories)) {
            $message = 'No instance factory available for source instance type ' . print_r($type, true);
            throw new \RuntimeException($message);
        }
        return $this->instanceFactories[$key];
    }

    /**
     * @param string $type
     * @return SourceLoader
     */
    public function getSourceLoaderByType($type)
    {
        $key = strtolower($type);
        if (!array_key_exists($key, $this->loaders)) {
            $message = 'No loader available for source instance type ' . print_r($type, true);
            throw new \RuntimeException($message);
        }
        return $this->loaders[$key];
    }

    /**
     * @param Source $source
     * @return SourceInstance
     */
    public function createInstance(Source $source)
    {
        return $this->getInstanceFactory($source)->createInstance($source);
    }

    public function fromConfig(array $data, $id)
    {
        if (empty($data['type'])) {
            throw new \RuntimeException("Missing mandatory value 'type' in given data");
        }
        return $this->getInstanceFactoryByType($data['type'])->fromConfig($data, $id);
    }

    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources)
    {
        $implementation = $this->getInstanceFactoryByType($instance->getSource()->getType());
        return $implementation->matchInstanceToPersistedSource($instance, $extraSources);
    }

    /**
     * Adds (or replaces) the sub-services for a concrete sub-type of source instances. Each service type expects
     * two handling services
     * 1) service that generates frontend configuration
     * 2) factory service for new instances
     *
     * Provided as a post-constructor customization hook. Should call from a DI compiler pass to plug in
     * support for new source types.
     *
     * @see RegisterWmsSourceServicePass for a working example
     *
     * @param string $instanceType
     * @param SourceService $configService
     * @param SourceInstanceFactory $instanceFactory
     * @param SourceLoader $loader
     */
    public function registerSubtypeService($instanceType, $configService, $instanceFactory, $loader)
    {
        if (!$instanceType || !is_string($instanceType)) {
            throw new \InvalidArgumentException('Empty / non-string instanceType ' . print_r($instanceType));
        }
        $key = strtolower($instanceType);
        if (!($configService instanceof SourceInstanceConfigGenerator)) {
            $type = is_object($configService) ? get_class($configService) : gettype($configService);
            throw new \InvalidArgumentException("Unsupported type {$type}, must be SourceService");
        }
        $this->configServices[$key] = $configService;
        if (!($instanceFactory instanceof SourceInstanceFactory)) {
            $type = is_object($instanceFactory) ? get_class($instanceFactory) : gettype($instanceFactory);
            throw new \InvalidArgumentException("Unsupported type {$type}, must be SourceInstanceFactory");
        }
        $this->instanceFactories[$key] = $instanceFactory;
        if (!($loader instanceof SourceLoader)) {
            $type = is_object($instanceFactory) ? get_class($instanceFactory) : gettype($instanceFactory);
            throw new \InvalidArgumentException("Unsupported type {$type}, must be SourceLoader");
        }
        $this->loaders[$key] = $loader;
    }

    /**
     * Returns list of assets of given type required for source instances to work on the client.
     *
     * @param Application $application
     * @return string[]
     */
    public function getScriptAssets(Application $application)
    {
        $refs = array();
        foreach ($this->configServices as $subTypeService) {
            $typeRefs = $subTypeService->getScriptAssets($application);
            if ($typeRefs) {
                $refs = array_merge($refs, $typeRefs);
            }
        }
        return $refs;
    }

    public function getFormType(SourceInstance $instance)
    {
        return $this->getInstanceFactory($instance->getSource())->getFormType($instance);
    }

    public function getFormTemplate(SourceInstance $instance)
    {
        return $this->getInstanceFactory($instance->getSource())->getFormTemplate($instance);
    }

    public function isInstanceEnabled(SourceInstance $sourceInstance)
    {
        return $this->getConfigGenerator($sourceInstance)->isInstanceEnabled($sourceInstance);
    }

    public function canDeactivateLayer(SourceInstanceItem $layer)
    {
        return $this->getConfigGenerator($layer->getSourceInstance())->canDeactivateLayer($layer);
    }
}
