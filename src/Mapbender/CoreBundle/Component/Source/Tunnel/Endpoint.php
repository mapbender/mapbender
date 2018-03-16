<?php

namespace Mapbender\CoreBundle\Component\Source\Tunnel;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Mapbender\WmsBundle\Component\InstanceTunnel;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tunnel base endpoint pre-bound to a particular SourceInstance
 */
class Endpoint
{
    /** @var InstanceTunnel */
    protected $service;

    /** @var SourceInstance */
    protected $instance;

    /** @var Source */
    protected $source;


    /**
     * InstanceTunnel constructor.
     * @param InstanceTunnel
     * @param SourceInstance $instance
     */
    public function __construct($service, SourceInstance $instance)
    {
        $this->service = $service;
        $this->instance = $instance;
        $this->source = $instance->getSource();
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
     * @return string
     */
    public function getInternalUrl(Request $request)
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
