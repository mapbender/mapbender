<?php

namespace Mapbender\CoreBundle\Component\Source\Tunnel;
use Doctrine\Common\Collections\Criteria;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tunnel base endpoint pre-bound to a particular SourceInstance
 */
class Endpoint
{
    /** @var InstanceTunnelService */
    protected $service;

    /** @var SourceInstance */
    protected $instance;

    /** @var Source */
    protected $source;


    /**
     * InstanceTunnel constructor.
     * @param InstanceTunnelService
     * @param SourceInstance $instance
     */
    public function __construct($service, SourceInstance $instance)
    {
        $this->service = $service;
        $this->instance = $instance;
        $this->source = $instance->getSource();
    }

    /**
     * @return InstanceTunnelService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Returns the URL base the Browser / JS client should use to access the tunnel.
     *
     * @return string
     */
    public function getPublicBaseUrl()
    {
        return $this->service->getPublicBaseUrl($this);
    }

    /**
     * Returns the URL the Browser / JS client should use to access a specific WMS function (by given URL) via
     * the tunnel.
     *
     * @param string $url NOTE: scheme/host/path completely ignored, only query string is relevant
     * @return string
     * @throws \RuntimeException if no REQUEST=... in given $url
     */
    public function generatePublicUrl($url)
    {
        return $this->service->generatePublicUrl($this, $url);
    }

    /**
     * @return Application
     */
    public function getApplicationEntity()
    {
        return $this->instance->getLayerset()->getApplication();
    }

    /**
     * @return SourceInstance
     */
    public function getSourceInstance()
    {
        return $this->instance;
    }

    /**
     * Gets the url on the wms service that satisfies the given $request (=Symfony Http Request object).
     * Get params from hidden vendor specifics and the given request are appended.
     * Params from the request override params from hidden vendorspecs with the same name.
     *
     * @param Request $request
     * @return string|null
     */
    public function getInternalUrl(Request $request)
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

    /**
     * @param Request $request
     * @param $instanceLayerId
     * @return string|null
     */
    public function getInternalLegendUrl(Request $request, $instanceLayerId)
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

    /**
     * @param Request $request
     * @return string|null
     */
    protected function getInternalBaseUrl(Request $request)
    {
        $requestType = RequestUtil::getGetParamCaseInsensitive($request, 'request', null);
        switch (strtolower($requestType)) {
            case 'getmap':
                return $this->source->getGetMap()->getHttpGet();
            case 'getfeatureinfo':
                return $this->source->getGetFeatureInfo()->getHttpGet();
            default:
                return null;
        }
    }

    /**
     * @param string $instanceLayerId
     * @return string|null
     */
    protected function getInternalLegendBaseUrl($instanceLayerId)
    {
        // @todo: integrate WMTS and WMTS legends properly; atm only WmsInstance has a defined 'getLayers' method
        if (!method_exists($this->instance, 'getLayers')) {
            return null;
        }
        /** @var WmsInstance $wmsInstance */
        $wmsInstance = $this->instance;

        $layerCriteria = Criteria::create()
            ->where(Criteria::expr()->eq('id', $instanceLayerId))
        ;
        /** @var SourceInstanceItem|false $layer */
        $layer = $wmsInstance->getLayers()->matching($layerCriteria)->first();
        if (!$layer || $layer->getSourceInstance()->getId() != $this->instance->getId()) {
            // instance layer is not connected to the source instance
            return null;
        }
        /** @var WmsLayerSource $layerSource */
        $layerSource = $layer->getSourceItem();
        return WmsInstanceLayerEntityHandler::getLegendUrlFromStyles($layerSource, false);
    }

    /**
     * @param $url
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getUrl($url)
    {
        $source = $this->instance->getSource();
        $urlWithCredentials = UrlUtil::addCredentials($url, $source->getUsername(), $source->getPassword());
        $response = $this->service->getHttpTransport()->getUrl($urlWithCredentials);
        foreach (array_keys($response->headers->getCookies()) as $cookieKey) {
            $response->headers->removeCookie($cookieKey);
        }
        return $response;
    }
}
