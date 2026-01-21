<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Component\Exception\SourceNotFoundException;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        protected string                $proxyRouteName = 'owsproxy3_core_owsproxy_entrypoint')
    {

    }

    public function getTunnelService(): InstanceTunnelService
    {
        return $this->tunnelService;
    }

    /**
     * Get base url for owsproxy controller action with no particular url.
     * Application config emits this for client-side proxy stripping / readding.
     */
    public function getProxyBaseUrl(): string
    {
        return $this->getProxyUrl(array(), UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Modify url to pass over Owsproxy controller action.
     */
    public function proxifyUrl(string $url): string
    {
        $params = array(
            'url' => $this->signer->signUrl($url),
        );
        return $this->getProxyUrl($params, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Tunnelify a fully-formed service request url.
     * This will add non-hidden vendor specifics and potentially other implicit parameters.
     *
     * @param string $url with additional GET params (every other part of the url is ignored).
     *        NOTE: AT least the 'request=...' paramter is required!
     * @throws \RuntimeException if no REQUEST=... in given $url
     */
    public function tunnelifyUrl(SourceInstance $instance, string $url = ''): string
    {
        return $this->tunnelService->getEndpoint($instance)->generatePublicUrl($url);
    }

    /**
     * Get the public base url of the instance tunnel action corresponding to given $instance.
     * This will include non-hidden vendor specifics and potentially other implicit parameters.
     */
    public function getPublicTunnelBaseUrl(SourceInstance $instance): string
    {
        return $this->tunnelService->getEndpoint($instance)->getPublicBaseUrl();
    }

    /**
     * Inverse of proxification / tunnelification.
     * Removes proxy controller wrappings, resolves tunnel urls to complete internal urls.
     * If input $url is neither proxified nor tunneled, it gets returned unmodified.
     *
     * @param bool $localOnly default false; to also include the host name in matching
     *             NOTE: enabling this will cause conflicts on subdomain load-balancing
     * @throws SourceNotFoundException on tunnel match to deleted instance
     */
    public function getInternalUrl(string $url, bool $localOnly = false): string
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

    public function stripProxySignature(string $url): string
    {
        return preg_replace('#(?<=[\?\&])_signature(=)?[^&\#]*#', '', $url);
    }

    /**
     * Convenience method if you already have access to this service but don't want to
     * inject the signer as well.
     */
    public function signUrl(string $url): string
    {
        return $this->signer->signUrl($url);
    }

    /**
     * @param string[] $params
     * @param int $refType one of the UrlGeneratorInterface constants
     */
    protected function getProxyUrl(array $params, int $refType): string
    {
        return $this->router->generate($this->proxyRouteName, $params, $refType);
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
