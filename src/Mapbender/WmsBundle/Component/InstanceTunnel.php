<?php


namespace Mapbender\WmsBundle\Component;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\RequestUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InstanceTunnel
{
    /** @var SourceInstance */
    protected $instance;

    /** @var Source */
    protected $source;

    /**
     * InstanceTunnel constructor.
     * @param SourceInstance $instance
     */
    public function __construct(SourceInstance $instance)
    {
        $this->instance = $instance;
        $this->source = $instance->getSource();
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

    /**
     * Returns the URL base the Browser / JS client should use to access the tunnel.
     *
     * @todo: TBD: bind Router (or entire Container) via constructor?
     *
     * @param UrlGeneratorInterface $router
     * @return string
     */
    public function getPublicBaseUrl(UrlGeneratorInterface $router)
    {
        return $router->generate(
            'mapbender_core_application_instancetunnel',
            array(
                'slug' => $this->instance->getLayerset()->getApplication()->getSlug(),
                'instanceId' => $this->instance->getId()),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Returns the URL the Browser / JS client should use to access a specific WMS function (by given URL) via
     * the tunnel.
     *
     * @param UrlGeneratorInterface $router
     * @param string $url NOTE: scheme/host/path completely ignored, only query string is relevant
     * @return string
     * @throws \RuntimeException if no REQUEST=... in given $url
     */
    public function generatePublicUrl(UrlGeneratorInterface $router, $url)
    {
        // require a "request" param, the tunnel action doesn't function without it
        $params = array();
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        foreach ($params as $name => $value) {
            if (strtolower($name) == 'request') {
                // @todo: validate if request value is in our supported set (GetMap, GetLegendGraphic, GetFeatureInfo)?
                $fullQueryString = strstr($url, '?', false);
                // forward ALL GET parameters in input url
                return $this->getPublicBaseUrl($router) . $fullQueryString;
            }
        }
        throw new \RuntimeException('Failed to tunnelify url, no `request` param found: ' . var_export($url, true));
    }
}
