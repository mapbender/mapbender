<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\Component\SourceInstanceFactory;
use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\DependencyInjection\Compiler\RegisterWmsSourceServicePass;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    /** @var ContainerInterface */
    protected $container;
    /** @var SourceService[] */
    protected $configServices = array();
    /** @var SourceInstanceFactory[] */
    protected $instanceFactories = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
        $service = ArrayUtil::getDefault($this->configServices, $key, null);
        if (!$service) {
            $message = 'No config generator available for source instance type ' . print_r($key, true);
            throw new \RuntimeException($message);
        }
        return $service;
    }

    /**
     * @param Source $source
     * @return SourceInstanceFactory
     */
    public function getInstanceFactory(Source $source)
    {
        $key = strtolower($source->getType());
        $service = ArrayUtil::getDefault($this->instanceFactories, $key, null);
        if (!$service) {
            $message = 'No instance factory available for source instance type ' . print_r($key, true);
            throw new \RuntimeException($message);
        }
        return $service;
    }

    /**
     * @param Source $source
     * @return SourceInstance
     */
    public function createInstance(Source $source)
    {
        return $this->getInstanceFactory($source)->createInstance($source);
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
     */
    public function registerSubtypeService($instanceType, $configService, $instanceFactory)
    {
        if (!$instanceType || !is_string($instanceType)) {
            throw new \InvalidArgumentException('Empty / non-string instanceType ' . print_r($instanceType));
        }
        $key = strtolower($instanceType);
        if (!$configService) {
            unset($this->configServices[$key]);
        } else {
            if (!($configService instanceof SourceService)) {
                $type = is_object($configService) ? get_class($configService) : gettype($configService);
                throw new \InvalidArgumentException("Unsupported type {$type}, must be SourceService");
            }
            $this->configServices[$key] = $configService;
        }
        if (!$instanceFactory) {
            unset($this->instanceFactories[$key]);
        } else {
            if (!($instanceFactory instanceof SourceInstanceFactory)) {
                $type = is_object($instanceFactory) ? get_class($instanceFactory) : gettype($instanceFactory);
                throw new \InvalidArgumentException("Unsupported type {$type}, must be SourceInstanceFactory");
            }
            $this->instanceFactories[$key] = $instanceFactory;
        }
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
