<?php


namespace Mapbender\WmsBundle\Component;


use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Symfony\Component\HttpFoundation\Request;

class InstanceTunnel
{
    /** @var SourceInstance */
    protected $instance;

    /**
     * InstanceTunnel constructor.
     * @param SourceInstance $instance
     */
    public function __construct(SourceInstance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Gets the url on the wms service that satisfies the given $request (=Symfony Http Request object)
     *
     * @param Request $request
     * @return string
     */
    public function getInternalUrl(Request $request)
    {
        $source = $this->instance->getSource();
        $requestType = RequestUtil::getGetParamCaseInsensitive($request, 'request', null);

        switch (strtolower($requestType)) {
            case 'getmap':
                return $source->getGetMap()->getHttpGet();
            case 'getfeatureinfo':
                return $source->getGetFeatureInfo()->getHttpGet();
            case 'getlegendgraphic':
                $glgMode = $request->query->get('_glgmode', null);
                $layerName = $request->query->get('layer', null);
                if (!$layerName) {
                    $glgMode = null;
                    $layerSource = null;
                } else {
                    $layerSource = WmsSourceEntityHandler::getLayerSourceByName($source, $layerName);
                    if (!$layerSource) {
                        $glgMode = null;
                    }
                }
                switch ($glgMode) {
                    default:
                        return $source->getGetLegendGraphic()->getHttpGet();
                    case 'styles':
                        return WmsInstanceLayerEntityHandler::getLegendUrlFromStyles($layerSource);
                    case 'GetLegendGraphic':
                        return WmsInstanceLayerEntityHandler::getLegendGraphicUrl($layerSource);
                }
                break;
            default:
                return null;
        }
    }
}
