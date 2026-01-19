<?php

namespace OwsProxy3\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use OwsProxy3\CoreBundle\Component\HttpFoundationClient;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Route prefix "owsproxy" set by attributes.yaml
 */
class OwsProxyController extends AbstractController
{
    public function __construct(
        protected HttpFoundationClient       $client,
        protected Signer                     $signer,
        private readonly ApplicationResolver $applicationResolver,
        private readonly InstanceTunnelService $instanceTunnelService,
    )
    {
    }

    /**
     * Handles the client's request
     *
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/')]
    public function entryPointAction(Request $request)
    {
        $url = $request->query->get('url');

        try {
            $proxy_query = ProxyQuery::createFromRequest($request, 'url');
            $this->signer->checkSignedUrl($url);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        } catch (\Mapbender\CoreBundle\Component\Exception\ProxySignatureException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        }
        return $this->getQueryResponse($proxy_query, $request);
    }

    #[Route(path: '/application/{slug}/instance/{instance}', name: 'owsproxy_sourceinstance')]
    public function sourceInstanceAction(Request $request, string|int $slug, string|int $instance)
    {
        // this already checks for view permission on the application
        $application = $this->applicationResolver->getApplicationEntity($slug);
        $instance = $application->getSourceInstanceById($instance, true);
        if (!$instance) {
            throw $this->createNotFoundException("No instance with id '".$instance."' found.");
        }

        $baseUrl = $this->instanceTunnelService->getEndpoint($instance)->getInternalUrl($request);
        $url = Utils::appendQueryParams($baseUrl, $request->query->all());
        $url = Utils::filterDuplicateQueryParams($url, false);
        $headers = Utils::getHeadersFromRequest($request);
        if ($request->getMethod() === 'POST') {
            $proxy_query = ProxyQuery::createPost($url, $request->getContent(), $headers);
        } else {
            $proxy_query = ProxyQuery::createGet($url, $headers);
        }

        return $this->getQueryResponse($proxy_query, $request);
    }

    /**
     * @param ProxyQuery $query
     * @param Request $request
     * @return Response
     */
    protected function getQueryResponse(ProxyQuery $query, Request $request)
    {
        $response = $this->client->handleQuery($query);
        $this->restoreOriginalCookies($response, $request);
        return $response;
    }

    protected function restoreOriginalCookies(Response $response, Request $request)
    {
        foreach ($request->cookies as $key => $value) {
            $response->headers->removeCookie($key);
            $response->headers->setCookie(new Cookie($key, $value));
        }
    }
}
