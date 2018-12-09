<?php


namespace Mapbender\CoreBundle\Component\Source\Tunnel;

use Mapbender\CoreBundle\Controller\ApplicationController;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Instance tunnel can both generate and evaluate requests / urls for WMS services that contain sensitive parameters
 * (credentials, "vendorSpecifics") that need to be hidden from the browser.
 *
 * @see ApplicationController::instanceTunnelAction()
 * @see WmsSourceService::postProcessUrls()
 * @see WmsInstanceLayerEntityHandler::getLegendConfig()
 *
 * By default registered in container as mapbender.source.instancetunnel.service, see services.xml
 */
class InstanceTunnelService
{
    /** @var UrlGeneratorInterface */
    protected $router;
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * InstanceTunnel constructor.
     * @param UrlGeneratorInterface $router
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(UrlGeneratorInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public function makeEndpoint(SourceInstance $instance)
    {
        return new Endpoint($this, $instance);
    }

    /**
     * Returns the URL base the Browser / JS client should use to access the tunnel.
     *
     * @param Endpoint $endpoint
     * @return string
     */
    public function getPublicBaseUrl(Endpoint $endpoint)
    {
        $vsHandler = new VendorSpecificHandler();
        $vsParams = $vsHandler->getPublicParams($endpoint->getSourceInstance(), $this->tokenStorage->getToken());
        return $this->router->generate(
            'mapbender_core_application_instancetunnel',
            array_replace($vsParams, array(
                'slug' => $endpoint->getApplicationEntity()->getSlug(),
                'instanceId' => $endpoint->getSourceInstance()->getId(),
            )),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Returns the URL the Browser / JS client should use to access a specific WMS function (by given URL) via
     * the tunnel.
     *
     * @param Endpoint $endpoint
     * @param string $url NOTE: scheme/host/path completely ignored, only query string is relevant
     * @return string
     * @throws \RuntimeException if no REQUEST=... in given $url
     */
    public function generatePublicUrl(Endpoint $endpoint, $url)
    {
        // require a "request" param, the tunnel action doesn't function without it
        $params = array();
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        foreach ($params as $name => $value) {
            if (strtolower($name) == 'request') {
                // @todo: validate if request value is in our supported set (GetMap, GetLegendGraphic, GetFeatureInfo)?
                $fullQueryString = strstr($url, '?', false);
                // forward ALL GET parameters in input url
                return $this->getPublicBaseUrl($endpoint) . $fullQueryString;
            }
        }
        throw new \RuntimeException('Failed to tunnelify url, no `request` param found: ' . var_export($url, true));
    }

    public function getHiddenParams(SourceInstance $instance)
    {
        $vsHandler = new VendorSpecificHandler();
        return $vsHandler->getHiddenParams($instance, $this->tokenStorage->getToken());
    }
}
