<?php

namespace Mapbender\CoreBundle\Component\Source\Tunnel;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
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
            $params = array_replace($hiddenParams, $request->query->all());
            return UrlUtil::validateUrl($baseUrl, $params);
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
            case 'getlegendgraphic':
                return $this->getInternalGetLegendGraphicUrl($request);
            default:
                return null;
        }
    }

    /**
     * Gets the url on the wms service that satisfies the given $request (=Symfony Http Request object)
     *
     * @param Request $request
     * @return string
     */
    public function getInternalGetLegendGraphicUrl(Request $request)
    {
        $glgMode = $request->query->get('_glgmode', null);
        $layerName = RequestUtil::getGetParamCaseInsensitive($request, 'layer', null);
        if (!$layerName) {
            $glgMode = null;
            $layerSource = null;
        } else {
            $layerSource = WmsSourceEntityHandler::getLayerSourceByName($this->source, $layerName);
            if (!$layerSource) {
                $glgMode = null;
            }
        }
        switch ($glgMode) {
            default:
                return $this->source->getGetLegendGraphic()->getHttpGet();
            case 'styles':
                return WmsInstanceLayerEntityHandler::getLegendUrlFromStyles($layerSource);
            case 'GetLegendGraphic':
                return WmsInstanceLayerEntityHandler::getLegendGraphicUrl($layerSource);
        }
    }
}
