<?php


namespace Mapbender\CoreBundle\Component\Source\Tunnel;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\Transport\HttpTransportInterface;
use Mapbender\CoreBundle\Component\Exception\SourceNotFoundException;
use Mapbender\CoreBundle\Component\Source\HttpOriginInterface;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Controller\ApplicationController;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\VendorSpecificHandler;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Instance tunnel can both generate and evaluate requests / urls for WMS services that contain sensitive parameters
 * (credentials, "vendorSpecifics") that need to be hidden from the browser.
 *
 * @see ApplicationController::instanceTunnelAction()
 * @see WmsSourceInstanceConfigGenerator::postProcessUrls()
 *
 * By default registered in container as mapbender.source.instancetunnel.service, see services.xml
 */
class InstanceTunnelService
{
    /** @var HttpTransportInterface */
    protected $httpTransport;
    /** @var RouterInterface */
    protected $router;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var Endpoint[] */
    protected $bufferedEndPoints = array();
    /** @var string */
    protected $tunnelRouteName;
    /** @var string */
    protected $legendTunnelRouteName;

    /**
     * @param HttpTransportInterface $httpTransprot
     * @param RouterInterface $router
     * @param TypeDirectoryService $sourceTypeDirectory
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(HttpTransportInterface $httpTransprot,
                                RouterInterface $router,
                                TypeDirectoryService $sourceTypeDirectory,
                                TokenStorageInterface $tokenStorage,
                                EntityManagerInterface $entityManager)
    {
        $this->httpTransport = $httpTransprot;
        $this->router = $router;
        $this->sourceTypeDirectory = $sourceTypeDirectory;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        // @todo: TBD if it's worth making this configurable
        $this->tunnelRouteName = 'mapbender_core_instancetunnel_instancetunnel';
        $this->legendTunnelRouteName = 'mapbender_core_instancetunnel_instancetunnellegend';
    }

    /**
     * Gets an Endpoint. May reuse the same Enpoint for the same SourceInstance (identity via
     * spl_object_hash).
     *
     * @param SourceInstance $instance
     * @return Endpoint
     * @todo: needs application binding for reusable source instances
     */
    public function getEndpoint(SourceInstance $instance)
    {
        $id = spl_object_hash($instance);
        if (!array_key_exists($id, $this->bufferedEndPoints)) {
            $this->bufferedEndPoints[$id] = $this->makeEndpoint($instance);
        }
        return $this->bufferedEndPoints[$id];
    }

    /**
     * Makes a new Endpoint, no reuse.
     *
     * @param SourceInstance $instance
     * @return Endpoint
     * @todo: needs application binding for reusable source instances
     */
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
        $params = array_replace($vsParams, array(
            'instanceId' => $endpoint->getSourceInstance()->getId(),
        ));

        return $this->router->generate($this->tunnelRouteName, $params);
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
     * @param WmsInstanceLayer|SourceInstanceItem $instanceLayer
     * @return string
     */
    public function generatePublicLegendUrl(SourceInstanceItem $instanceLayer)
    {
        $sourceInstance = $instanceLayer->getSourceInstance();
        return $this->router->generate($this->legendTunnelRouteName, array(
            'instanceId' => $sourceInstance->getId(),
            'layerId' => $instanceLayer->getId(),
        ));
    }

    /**
     * Returns the internal url associated with the given public url.
     *
     * @param Request $request
     * @param bool $includeCredentials
     * @param bool $localOnly default false; to also include the host name in matching
     *             NOTE: enabling this will cause conflicts on subdomain load-balancing
     * @return string|null
     */
    public function getInternalUrl(Request $request, $includeCredentials, $localOnly = false)
    {
        $routerMatch = UrlUtil::routeParamsFromUrl($this->router, $request->getUri(), !$localOnly);
        if ($routerMatch) {
            $endPoint = $this->matchRouteParams($routerMatch);
            if ($endPoint) {
                switch ($routerMatch['_route']) {
                    case $this->tunnelRouteName:
                        $urlNoCredentials = $endPoint->getInternalUrl($request);
                        break;
                    case $this->legendTunnelRouteName:
                        $instanceLayerId = $routerMatch['layerId'];
                        $urlNoCredentials = $endPoint->getInternalLegendUrl($request, $instanceLayerId);
                        break;
                    default:
                        throw new \LogicException("Unhandled route {$routerMatch['_route']}");
                }
                if ($includeCredentials) {
                    $source = $endPoint->getSourceInstance()->getSource();
                    if ($source instanceof HttpOriginInterface) {
                        return UrlUtil::addCredentials($urlNoCredentials, $source->getUsername(), $source->getPassword());
                    }
                }
                return $urlNoCredentials;
            }
        }
        return null;
    }

    /**
     * @param mixed[] $routerMatch return value from UrlMatcherInterface::match
     * @return Endpoint|null
     * @throws SourceNotFoundException if route matched but entity missing from db repository
     */
    protected function matchRouteParams($routerMatch)
    {
        $matchedRouteName = $routerMatch['_route'];
        if ($matchedRouteName === $this->tunnelRouteName || $matchedRouteName === $this->legendTunnelRouteName) {
            $instanceId = $routerMatch['instanceId'];
            $repository = $this->entityManager->getRepository(SourceInstance::class);
            /** @var SourceInstance|null $entity */
            $entity = $repository->find($instanceId);
            if ($entity) {
                return $this->getEndpoint($entity);
            } else {
                throw new SourceNotFoundException();
            }
        }
        // no match
        return null;
    }

    /**
     * @param SourceInstance $instance
     * @return string[]
     */
    public function getHiddenParams(SourceInstance $instance)
    {
        $vsHandler = new VendorSpecificHandler();
        return $vsHandler->getHiddenParams($instance, $this->tokenStorage->getToken());
    }

    /**
     * @return HttpTransportInterface
     */
    public function getHttpTransport()
    {
        return $this->httpTransport;
    }

    /**
     * @return TypeDirectoryService
     */
    public function getSourceTypeDirectory()
    {
        return $this->sourceTypeDirectory;
    }
}
