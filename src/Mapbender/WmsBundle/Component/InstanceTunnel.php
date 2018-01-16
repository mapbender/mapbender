<?php


namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Controller\ApplicationController;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Instance tunnel can both generate and evaluate requests / urls for WMS services that contain sensitive parameters
 * (credentials, "vendorSpecifics") that need to be hidden from the browser.
 *
 * @see ApplicationController::instanceTunnelAction()
 * @see WmsInstanceEntityHandler::getConfiguration()
 * @see WmsInstanceLayerEntityHandler::getLegendConfig()
 *
 * @package Mapbender\WmsBundle\Component
 */
class InstanceTunnel extends InstanceTunnelHandler
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * InstanceTunnel constructor.
     * @param UrlGeneratorInterface $router
     * @param SourceInstance $instance
     */
    public function __construct(UrlGeneratorInterface $router, SourceInstance $instance)
    {
        $this->router = $router;
        parent::__construct($instance);
    }

    /**
     * Returns the URL base the Browser / JS client should use to access the tunnel.
     *
     * @return string
     */
    public function getPublicBaseUrl()
    {
        return $this->router->generate(
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
     * @param string $url NOTE: scheme/host/path completely ignored, only query string is relevant
     * @return string
     * @throws \RuntimeException if no REQUEST=... in given $url
     */
    public function generatePublicUrl($url)
    {
        // require a "request" param, the tunnel action doesn't function without it
        $params = array();
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        foreach ($params as $name => $value) {
            if (strtolower($name) == 'request') {
                // @todo: validate if request value is in our supported set (GetMap, GetLegendGraphic, GetFeatureInfo)?
                $fullQueryString = strstr($url, '?', false);
                // forward ALL GET parameters in input url
                return $this->getPublicBaseUrl() . $fullQueryString;
            }
        }
        throw new \RuntimeException('Failed to tunnelify url, no `request` param found: ' . var_export($url, true));
    }
}
