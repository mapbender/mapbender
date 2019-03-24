<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Component\Exception\SourceNotFoundException;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Mangles and unmangles source urls.
 */
class UrlProcessor
{
    /** @var RouterInterface */
    protected $router;
    /** @var Signer */
    protected $signer;
    /** @var InstanceTunnelService */
    protected $tunnelService;
    /** @var string */
    protected $proxyRouteName;

    /**
     * @param RouterInterface $router
     * @param Signer $signer
     * @param InstanceTunnelService $tunnelService
     * @param string $proxyRouteName
     */
    public function __construct(RouterInterface $router,
                                Signer $signer,
                                InstanceTunnelService $tunnelService,
                                $proxyRouteName = 'owsproxy3_core_owsproxy_entrypoint')
    {
        $this->router = $router;
        $this->signer = $signer;
        $this->tunnelService = $tunnelService;
        $this->proxyRouteName = $proxyRouteName;
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
     * Tunnelify a fully-formed service request url.
     * This will add non-hidden vendor specifics and potentially other implicit parameters.
     *
     * @param SourceInstance $instance
     * @param string $url with additional GET params (every other part of the url is ignored).
     *        NOTE: AT least the 'request=...' paramter is required!
     * @return string
     * @throws \RuntimeException if
     */
    public function tunnelifyUrl(SourceInstance $instance, $url='')
    {
        return $this->tunnelService->getEndpoint($instance)->generatePublicUrl($url);
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
}
