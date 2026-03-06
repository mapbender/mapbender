<?php

namespace Mapbender\CoreBundle\Component\Source\Tunnel;
use Doctrine\Common\Collections\Criteria;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\HttpParsedSource;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tunnel base endpoint pre-bound to a particular SourceInstance
 */
class Endpoint
{
    /** @var HttpParsedSource */
    protected Source $source;

    public function __construct(
        protected InstanceTunnelService $service,
        protected Application $application,
        protected SourceInstance $instance
    )
    {
        $this->source = $instance->getSource();
    }

    public function getService(): InstanceTunnelService
    {
        return $this->service;
    }

    /**
     * Returns the URL base the Browser / JS client should use to access the tunnel.
     */
    public function getPublicBaseUrl(): string
    {
        return $this->service->getPublicBaseUrl($this);
    }

    /**
     * Returns the URL the Browser / JS client should use to access a specific WMS function (by given URL) via
     * the tunnel.
     *
     * @param string $url NOTE: scheme/host/path completely ignored, only query string is relevant
     * @throws \RuntimeException if no REQUEST=... in given $url
     */
    public function generatePublicUrl(string $url): string
    {
        return $this->service->generatePublicUrl($this, $url);
    }

    public function getApplicationEntity(): Application
    {
        return $this->application;
    }

    public function getSourceInstance(): SourceInstance
    {
        return $this->instance;
    }

    /**
     * Gets the url on the wms service that satisfies the given $request (=Symfony Http Request object).
     * Get params from hidden vendor specifics and the given request are appended.
     * Params from the request override params from hidden vendorspecs with the same name.
     */
    public function getInternalUrl(Request $request): ?string
    {
        $baseUrl = $this->getInternalBaseUrl($request);
        if ($baseUrl) {
            $hiddenParams = $this->service->getHiddenParams($this->instance);
            $requestParams = $request->query->all();
            $params = array_replace($hiddenParams, $requestParams);
            return UrlUtil::validateUrl($baseUrl, $params);
        } else {
            return null;
        }
    }

    public function getInternalLegendUrl(Request $request, $instanceLayerId): ?string
    {
        $layerLegendUrl = $this->getInternalLegendBaseUrl($instanceLayerId);
        if ($layerLegendUrl) {
            $hiddenParams = $this->service->getHiddenParams($this->instance);
            // add / prioritize query params from $request
            $params = array_replace($hiddenParams, $request->query->all());
            return UrlUtil::validateUrl($layerLegendUrl, $params);
        } else {
            return null;
        }
    }

    protected function getInternalBaseUrl(Request $request): ?string
    {
        $requestType = RequestUtil::getGetParamCaseInsensitive($request, 'request', null);
        switch (strtolower($requestType)) {
            case 'getmap':
                // TODO: this is WMS specific
                return $this->source->getGetMap()->getHttpGet();
            case 'getfeatureinfo':
                // TODO: this is WMS specific
                return $this->source->getGetFeatureInfo()->getHttpGet();
            default:
                return null;
        }
    }

    protected function getInternalLegendBaseUrl(string $instanceLayerId): ?string
    {
        // @todo: integrate WMTS and WMTS legends properly; atm only WmsInstance has a defined 'getLayers' method
        if (!method_exists($this->instance, 'getLayers')) {
            return null;
        }
        /** @var WmsInstance $wmsInstance */
        $wmsInstance = $this->instance;

        if ($this->application->isDbBased()) {
            // for db based application, layerId is always an int, layerId must be cast to int for matching
            $instanceLayerId = intval($instanceLayerId);
        }
        $layerCriteria = Criteria::create()->where(Criteria::expr()->eq('id', $instanceLayerId));
        /** @var SourceInstanceItem|false $layer */
        $layer = $wmsInstance->getLayers()->matching($layerCriteria)->first();
        if (!$layer || $layer->getSourceInstance()->getId() != $this->instance->getId()) {
            // instance layer is not connected to the source instance
            return null;
        }
        $configGenerator = $this->service->getSourceTypeDirectory()->getConfigGenerator($wmsInstance);
        return $configGenerator->getInternalLegendUrl($layer);
    }

    public function getUrl(string $url): Response
    {
        $source = $this->instance->getSource();
        if (!$source instanceof HttpOriginInterface) {
            throw new \RuntimeException("Source instance {$this->instance->getId()} is not a HttpSource");
        }

        $urlWithCredentials = UrlUtil::addCredentials($url, $source->getUsername(), $source->getPassword());
        $response = $this->service->getHttpTransport()->getUrl($urlWithCredentials);
        foreach (array_keys($response->headers->getCookies()) as $cookieKey) {
            $response->headers->removeCookie($cookieKey);
        }
        return $response;
    }
}
