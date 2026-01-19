<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Component\Exception\SourceNotFoundException;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * Mangles and unmangles source urls.
 */
class UrlProcessor
{
    public function __construct(
        protected RouterInterface       $router,
        protected Signer                $signer,
        protected InstanceTunnelService $tunnelService,
        protected RequestStack          $requestStack,
        protected string                $proxyRouteName = 'owsproxy3_core_owsproxy_entrypoint')
    {
    }

    /**
     * @return InstanceTunnelService
     */
    public function getTunnelService()
    {
        return $this->tunnelService;
    }

    /**
     * Get base url for owsproxy controller action with no particular url.
     * Application config emits this for client-side proxy stripping / readding.
     *
     * @return string
     */
    public function getProxyBaseUrl()
    {
        return $this->getProxyUrl(array(), RouterInterface::ABSOLUTE_PATH);
    }

    public function proxyUrlForInstance(WmsInstance $sourceInstance, Application $application = null): string
    {
        $slug = $application?->getSlug();

        if (!$application && $sourceInstance->getLayerset()) {
            $slug = $sourceInstance->getLayerset()->getApplication()->getSlug();
        } elseif (!$application) {
            // for shared instances, the layerset is not bound to the instance. Try to get slug from request
            $routeParams = $this->requestStack->getCurrentRequest()->get('_route_params');
            if (isset($routeParams['slug'])) {
                $slug = $routeParams['slug'];
            }
        }

        if (!$slug) {
            throw new \Exception("Could not determine application while trying to proxyify instance '".$sourceInstance->getId()."'");
        }

        return $this->router->generate('owsproxy_sourceinstance', [
            'instance' => $sourceInstance->getId(),
            'slug' => $slug,
        ]);
    }

    /**
     * Modify url to pass over Owsproxy controller action.
     *
     * @param string $url
     * @return string
     */
    public function proxifyUrl($url)
    {
        $params = array(
            'url' => $this->signer->signUrl($url),
        );
        return $this->getProxyUrl($params, RouterInterface::ABSOLUTE_PATH);
    }

    /**
     * Get the public base url of the instance tunnel action corresponding to given $instance.
     * This will include non-hidden vendor specifics and potentially other implicit parameters.
     *
     * @param SourceInstance $instance
     * @return string
     */
    public function getPublicTunnelBaseUrl(SourceInstance $instance)
    {
        return $this->tunnelService->getEndpoint($instance)->getPublicBaseUrl();
    }

    /**
     * Inverse of proxification / tunnelification.
     * Removes proxy controller wrappings, resolves tunnel urls to complete internal urls.
     * If input $url is neither proxified nor tunneled, it gets returned unmodified.
     *
     * @param string $url
     * @param bool $localOnly default false; to also include the host name in matching
     *             NOTE: enabling this will cause conflicts on subdomain load-balancing
     * @return string
     * @throws SourceNotFoundException on tunnel match to deleted instance
     */
    public function getInternalUrl($url, $localOnly = false)
    {
        $routerMatch = UrlUtil::routeParamsFromUrl($this->router, $url, !$localOnly);
        if ($routerMatch) {
            if ($routerMatch['_route'] === $this->proxyRouteName) {
                $fullRequest = Request::create($url);
                $urlParam = $fullRequest->query->get('url');
                $otherParams = $fullRequest->query->all();
                unset($otherParams['url']);
                unset($otherParams['_signature']);
                $baseUrl = $this->stripProxySignature($urlParam);
                return UrlUtil::validateUrl($baseUrl, $otherParams);
            } else {
                $tunnelInternalUrl = $this->tunnelService->getInternalUrl(Request::create($url), true);
                if ($tunnelInternalUrl) {
                    return $tunnelInternalUrl;
                }
            }
        }
        // no match, pass back unchanged
        return $url;
    }

    /**
     * @param string $url
     * @return string
     */
    public function stripProxySignature($url)
    {
        return preg_replace('#(?<=[\?\&])_signature(=)?[^&\#]*#', '', $url);
    }

    /**
     * Convenience method if you already have access to this service but don't want to
     * inject the signer as well.
     *
     * @param string $url
     * @return string
     */
    public function signUrl($url)
    {
        return $this->signer->signUrl($url);
    }

    /**
     * @param string[] $params
     * @param int $refType one of the UrlGeneratorInterface constants
     * @return string
     */
    protected function getProxyUrl($params, $refType)
    {
        return $this->router->generate($this->proxyRouteName, $params, $refType);
    }

    /**
     * @return RouterInterface
     */
    public function getRouter()
    {
        return $this->router;
    }
}
