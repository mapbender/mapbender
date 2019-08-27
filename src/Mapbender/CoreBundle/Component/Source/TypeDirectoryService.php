<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\Component\Loader\RefreshableSourceLoader;
use Mapbender\Component\SourceInstanceFactory;
use Mapbender\Component\SourceLoader;
use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
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
class TypeDirectoryService implements SourceInstanceFactory
{
    /** @var SourceService[] */
    protected $configServices = array();
    /** @var SourceInstanceFactory[] */
    protected $instanceFactories = array();
    /** @var SourceLoader[] */
    protected $loaders = array();

    /**
     * Get the appropriate service to deal with the given SourceInstance child class.
     *
     * To extend the list of available source instance handling services, @see getSourceServices
     *
     * @param SourceInstance $sourceInstance
     * @return SourceService|null
     */
    public function getSourceService(SourceInstance $sourceInstance)
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
     * @param string $type
     * @return bool
     */
    public function getRefreshSupprtByType($type)
    {
        $loader = $this->getSourceLoaderByType($type);
        return ($loader instanceof RefreshableSourceLoader);
    }

    /**
     * @param Source $source
     * @return bool
     */
    public function getRefreshSupport(Source $source)
    {
        return $this->getRefreshSupprtByType($source->getType());
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
        if (!($configService instanceof SourceService)) {
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
     * @param string $type must be 'js' or 'trans'
     * @return string[]
     */
    public function getAssets(Application $application, $type)
    {
        $refs = array();
        foreach ($this->configServices as $subTypeService) {
            $typeRefs = $subTypeService->getAssets($application, $type);
            if ($typeRefs) {
                $refs = array_merge($refs, $typeRefs);
            }
        }
        return $refs;
    }
}
