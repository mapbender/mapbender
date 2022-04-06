<?php
namespace OwsProxy3\CoreBundle\Controller;

use Mapbender\CoreBundle\Component\Signer;
use OwsProxy3\CoreBundle\Component\HttpFoundationClient;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author A.R.Pour
 * @author P. Schmidt
 */
class OwsProxyController
{
    /** @var HttpFoundationClient */
    protected $client;
    /** @var Signer */
    protected $signer;

    public function __construct(HttpFoundationClient $client,
                                Signer $signer)
    {
        $this->client = $client;
        $this->signer = $signer;
    }

    /**
     * Handles the client's request
     *
     * @Route("/")
     * @param Request $request
     * @return Response
     */
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
