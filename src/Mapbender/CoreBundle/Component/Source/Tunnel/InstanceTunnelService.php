<?php


namespace Mapbender\CoreBundle\Component\Source\Tunnel;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Component\Exception\SourceNotFoundException;
use Mapbender\CoreBundle\Controller\ApplicationController;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * Instance tunnel can both generate and evaluate requests / urls for WMS services that contain sensitive parameters
 * (credentials, "vendorSpecifics") that need to be hidden from the browser.
 *
 * @see ApplicationController::instanceTunnelAction()
 * @see WmsInstanceEntityHandler::getConfiguration()
 * @see WmsInstanceLayerEntityHandler::getLegendConfig()
 *
 * By default registered in container as mapbender.source.instancetunnel.service, see services.xml
 */
class InstanceTunnelService
{
    /** @var Router */
    protected $router;
    /** @var ContainerInterface */
    protected $container;
    /** @var ObjectRepository */
    protected $instanceRepository;

    /**
     * InstanceTunnel constructor.
     * @param Router $router
     * @param Registry $doctrine
     * @param ContainerInterface $container only used for VendorSpecifcHandler
     *      @todo: resolve container dependency of SourceInstanteEntityHandler, then remove container dependency of this class
     */
    public function __construct(Router $router, ContainerInterface $container, Registry $doctrine = null)
    {
        $this->router = $router;
        $this->instanceRepository = $doctrine->getRepository('Mapbender\CoreBundle\Entity\SourceInstance');
        $this->container = $container;
    }

    public function makeEndpoint(SourceInstance $instance)
    {
        return new Endpoint($this, $instance);
    }

    /**
     * @return ContainerInterface
     * @deprecated
     * @internal  Only used by Endpoint to initialize a SourceInstanteEntityHandler to extract 'vendor specifics'
     * @todo: resolve container dependency of VendorSpecificHandler
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Returns the URL base the Browser / JS client should use to access the tunnel.
     *
     * @param Endpoint $endpoint
     * @return string
     */
    public function getPublicBaseUrl(Endpoint $endpoint)
    {
        return $this->router->generate(
            'mapbender_core_application_instancetunnel',
            array(
                'slug' => $endpoint->getApplicationEntity()->getSlug(),
                'instanceId' => $endpoint->getSourceInstance()->getId(),
            ),
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

    /**
     * Tries to match the given $url to the known tunnel instance url, and returns the corresponding Endpoint
     * bound to the identified source instance.
     *
     * If the given URL does not go to the tunnel route, return null.
     *
     * @param string $url
     * @return Endpoint|null
     * @throws SourceNotFoundException (only) if url targets tunnel, but the referenced instance does not exist
     */
    public function endpointFromUrl($url)
    {
        $queryParts = explode('?', $url, 2);
        if (!$queryParts || empty($queryParts[0])) {
            throw new \InvalidArgumentException("Not a matchable url " . var_export($url, true));
        }
        // extracting the 'path info' from the url for a router->match
        // requires a base url. This isn't as straightforward as it seems..
        $baseUrl = $this->router->generate('mapbender_start', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        if (0 !== strpos($queryParts[0], $baseUrl)) {
            return null;
        }
        $urlPathInfo = substr($queryParts[0], strlen(rtrim($baseUrl, '/')));
        try {
            $routeMatch = $this->router->match($urlPathInfo);
            $instanceId = $routeMatch['instanceId'];
        } catch (ResourceNotFoundException $e) {
            // not an internal route
            return null;
        }
        /** @var SourceInstance|null $instance */
        $instance = $this->instanceRepository->find($instanceId);
        if (!$instance) {
            throw new SourceNotFoundException("No source with id " . var_export($instanceId, true));
        }
        return $this->makeEndpoint($instance);
    }
}
