<?php


namespace Mapbender\PrintBundle\Component\Service;


use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;

/**
 * Absorbs the continuity gap between old-style pseudo-service print service and actually servicy print service.
 *
 * Also hosts plugins for PrintClient Element.
 *
 * Registered in container at mapbender.print_service_bridge.service (yes, exactly like that)
 * @deprecated inject / access PrintService (service id "mapbender.print.service") and / or plugin host (service id "mapbender.print.plugin_host.service) directly
 * @todo v3.4: remove
 */
class PrintServiceBridge implements PrintServiceInterface
{
    /** @var PrintServiceInterface */
    protected $printService;
    /** @var PrintPluginHost */
    protected $pluginHost;

    private static $instance = null;

    public function __construct(PrintServiceInterface $printService, PrintPluginHost $pluginHost)
    {
        // strict singleton enforcement: no two instances may be created, including child class instances
        if (self::$instance) {
            throw new \LogicException(get_class($this) . " is strictly a singleton");
        } else {
            self::$instance = $this;
        }
        $this->printService = $printService;
        $this->pluginHost = $pluginHost;
    }

    /**
     * @return PrintPluginHost
     */
    public function getPluginHost()
    {
        return $this->pluginHost;
    }

    /**
     * @param Request $request
     * @param Element $elementEntity
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    final public function handleHttpRequest(Request $request, Element $elementEntity)
    {
        return $this->pluginHost->handleHttpRequest($request, $elementEntity);
    }

    /**
     * @param array $printJobData
     * @return string the (binary) body of the generated PDF
     */
    public function dumpPrint(array $printJobData)
    {
        return $this->printService->dumpPrint($printJobData);
    }

    public function storePrint(array $printJobData, $fileName)
    {
        return $this->printService->storePrint($printJobData, $fileName);
    }

    /**
     * Returns whatever is currently configured as the print service, which may or may not implement certain interfaces,
     * and may or may not extend from a known base class.
     *
     * @return PrintServiceInterface|\Mapbender\PrintBundle\Component\PrintService|object
     * @deprecated inject / access PrintService (service id "mapbender.print.service") directly
     */
    public function getPrintServiceInstance()
    {
        return $this->printService;
    }
}
