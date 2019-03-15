<?php

namespace Mapbender\CoreBundle\Component\Source;

use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\Application;
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
 * * @see TypeDirectoryService::setDefaultService
 * * @see TypeDirectoryService::registerSubtypeService
 *
 * This should be done in a DI compiler pass (extending service definition via XML / YAML does not work across bundles)
 * @see RegisterWmsSourceServicePass for a working example
 */
class TypeDirectoryService
{
    /** @var ContainerInterface */
    protected $container;
    /** @var SourceService[] */
    protected $subtypeServices = array();
    /** @var SourceService|null */
    protected $defaultService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $service = ArrayUtil::getDefault($this->subtypeServices, $key, null);
        if (!$service) {
            $service = $this->defaultService;
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
            // NOTE: The service id may have come from $this->defaultService.
            //       This is harmless. The sourceServices array is checked first, and the worst that can happen
            //       is that we create an implicit entry for every type that uses the default service.
            $this->subtypeServices[$key] = $service;
        }
        return $service;
    }

    /**
     * Sets (or removes) the default service for generating source instance configuration. This default
     * service is used as the fallback if the specific type ("wms") has not been explicitly wired to a
     * specialized service via @see registerSubtypeService
     *
     * The default is set via services.xml. This should not require compiler pass DI, though that's possible, too.
     *
     * @param SourceService|string $service
     */
    public function setDefaultService($service = null)
    {
        $this->defaultService = $this->validateServiceType($service, true);
    }

    /**
     * Adds (or replaces) the sub-service for generating configuration for a concrete sub-type of source instances.
     *
     * Provided as a post-constructor customization hook. Should call from a DI compiler pass to plug in
     * support for new source types.
     *
     * @see RegisterWmsSourceServicePass for a working example
     *
     * @param string $instanceType
     * @param SourceService|string $service service instance or the service id string (for lazy evaluation)
     */
    public function registerSubtypeService($instanceType, $service)
    {
        if (!$instanceType || !is_string($instanceType)) {
            throw new \InvalidArgumentException('Empty / non-string instanceType ' . print_r($instanceType));
        }
        $key = strtolower($instanceType);
        $service = $this->validateServiceType($service, true);
        if (!$service) {
            unset($this->subtypeServices[$key]);
        } else {
            $this->subtypeServices[$key] = $service;
        }
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
    protected function validateServiceType($serviceOrId, $allowEmpty)
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
        foreach ($this->subtypeServices as $subTypeService) {
            $typeRefs = $subTypeService->getAssets($application, $type);
            if ($typeRefs) {
                $refs = array_merge($refs, $typeRefs);
            }
        }
        return $refs;
    }
}
