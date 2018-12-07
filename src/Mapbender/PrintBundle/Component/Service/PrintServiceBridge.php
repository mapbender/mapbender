<?php


namespace Mapbender\PrintBundle\Component\Service;


use Mapbender\PrintBundle\Component\Plugin\PrintClientHttpPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Absorbs the continuity gap between old-style pseudo-service print service and actually servicy print service.
 *
 * Also hosts plugins for PrintClient Element.
 *
 * Registered in container at mapbender.print_service_bridge.service (yes, exactly like that)
 */
class PrintServiceBridge implements PrintServiceInterface
{
    /** @var ContainerInterface */
    protected $container;
    /** @var PrintClientHttpPluginInterface[] */
    protected $httpPlugins = array();

    private static $instance = null;

    /** @var object|false|null */
    protected $servicyPrintService;

    /** @var string|false|null */
    protected $unservicyPrintServiceClassName;

    /**
     * @param ContainerInterface $container only used for instantiation old-style non-service service
     */
    public function __construct(ContainerInterface $container)
    {
        // strict singleton enforcement: no two instances may be created, including child class instances
        if (self::$instance) {
            throw new \LogicException(get_class($this) . " is strictly a singleton");
        } else {
            self::$instance = $this;
        }
        $this->container = $container;
    }

    /**
     * Register a plugin at runtime.
     * This is intended to be called by container compiler passes.
     *
     * @param string|object $plugin container service id or instance
     */
    public function registerPlugin($plugin)
    {
        if (is_string($plugin)) {
            $plugin = $this->container->get($plugin);
        }
        $valid = false;
        $t = is_object($plugin) ? get_class($plugin) : gettype($plugin);
        if ($plugin instanceof PrintClientHttpPluginInterface) {
            $key = $plugin->getDomainKey();
            if (!$key || !is_string($key)) {
                throw new \LogicException("Invalid domain key " . print_r($key, true) . " from plugin type {$t}");
            }
            $this->httpPlugins[$key] = $plugin;
            $valid = true;
        }
        // @todo: support plugins for other stuff (modifying pdf, modifying map image) here?
        if (!$valid) {
            throw new \InvalidArgumentException("Type {$t} does not implement a supported plugin interface, don't know what to do with it");
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    final public function handleHttpRequest(Request $request)
    {
        foreach ($this->httpPlugins as $plugin) {
            $responseOrNull = $plugin->handleHttpRequest($request);
            if ($responseOrNull) {
                return $responseOrNull;
            }
        }
        // not handled by any plugin
        return null;
    }

    /**
     * @param array $printJobData
     * @return string the (binary) body of the generated PDF
     */
    public function buildPdf(array $printJobData)
    {
        $serviceOrMaybeNot = $this->getPrintServiceInstance();
        if ($serviceOrMaybeNot instanceof PrintServiceInterface) {
            return $serviceOrMaybeNot->buildPdf($printJobData);
        } else {
            // Legacy path
            // NOTE: Retrieved object could be anything, not even necessarily a child of the old default class.
            /** @var \Mapbender\PrintBundle\Component\PrintService $serviceOrMaybeNot */
            return $serviceOrMaybeNot->doPrint($printJobData);
        }
    }

    /**
     * Returns whatever is currently configured as the print service, which may or may not implement certain interfaces,
     * and may or may not extend from a known base class.
     *
     * @return PrintServiceInterface|\Mapbender\PrintBundle\Component\PrintService|object
     */
    public function getPrintServiceInstance()
    {
        if ($this->servicyPrintService === null) {
            $serviceInstance = $this->container->get('mapbender.print.service', ContainerInterface::NULL_ON_INVALID_REFERENCE);
            $classNameParam = $this->container->getParameter('mapbender.print.service.class');
            if (!$serviceInstance) {
                if (!$classNameParam) {
                    throw new \LogicException("Without a registered mapbender.print.service, container param mapbender.print.service.class MUST be defined and non-empty");
                }
                $this->servicyPrintService = false;
                $this->unservicyPrintServiceClassName = $classNameParam;
            } elseif ($classNameParam && get_class($serviceInstance) != $classNameParam) {
                throw new \LogicException("Container-registered mapbender.print.service is not an instance of container param mapbender.print.service.class");
            } elseif (!$serviceInstance instanceof PrintServiceInterface) {
                throw new \LogicException("Registered mapbender.print.service does not implement the required PrintServiceInterface");
            } else {
                $this->servicyPrintService = $serviceInstance;
                $this->unservicyPrintServiceClassName = false;
            }
        }
        if ($this->servicyPrintService) {
            return $this->servicyPrintService;
        } else {
            $cn = $this->unservicyPrintServiceClassName;
            // Old-style print accumulates job state
            // => instantiate new every time
            return new $cn($this->container);
        }
    }
}
