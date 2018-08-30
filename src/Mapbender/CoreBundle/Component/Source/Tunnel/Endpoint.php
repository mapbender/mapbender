<?php

namespace Mapbender\CoreBundle\Component\Source\Tunnel;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Controller\ApplicationController;
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
     * Gets the url on the wms service that satisfies the given $request (=Symfony Http Request object)
     *
     * @param Request $request
     * @param bool $appendQuery to add query params from Request; this is off by default for legacy reasons (GET
     *     params were added in @see ApplicationController::instanceTunnelAction()
     * @return string
     */
    public function getInternalUrl(Request $request, $appendQuery = false)
    {
        $baseUrl = $this->getInternalBaseUrl($request);
        if ($appendQuery) {
            $instHandler = SourceInstanceEntityHandler::createHandler($this->service->getContainer(), $this->instance);
            $vendorSpec  = $instHandler->getSensitiveVendorSpecific();
            /* replace vendorspecific parameters already explicitly given in GET */
            $params = array_replace($vendorSpec, $request->query->all());
            return UrlUtil::appendQueryParams($baseUrl, $params);
        } else {
            return $baseUrl;
        }
    }

    /**
     * Gets the base url on the wms service that satisfies the given $request (=Symfony Http Request object)
     *
     * @param Request $request
     * @return string
     */
    public function getInternalBaseUrl(Request $request)
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
                // @todo: throw?
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
