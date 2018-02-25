<?php

namespace Mapbender\CoreBundle\Component\OwsProxy;

use Buzz\Message\Response;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\Exception\HTTPStatus502Exception;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Legacy "owsproxy3"-based http client.
 *
 * @deprecated
 * @internal
 *
 * Known properties:
 * - All requests return BuzzMessage.
 * - All upstream connection errors (DNS, route, HTTP error responses) are ignored and time out.
 * - Supplying a URL without a host is the only error condition that throws (a custom HTTPStatus502Exception, with
 *   a truncated stack trace)
 *
 * Absorbed from owsproxy3 repository, where it lived a short, undocumented life and will be reverted.
 * @see https://github.com/mapbender/owsproxy3/compare/f7a3dc86ac0eac4896e55a577c5416814a491f11...65b66009417aca618235ec4c76100d2bb4399dac
 * It didn't even function (container initalization was broken).
 *
 * This service and its underlying machinery should remain in place as is to support http response consumers that
 * only know how to deal with Buzz Response objects.
 *
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class LegacyService extends ContainerAware
{
    /**
     * ProxyService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * Fetches a response from the given url with the given parameters.
     * Method is GET, unless $content (raw POST body) or $postParams (key => value mapping) is supplied, then the
     * method automatically becomes POST.
     *
     * $content and $postParams are **NOT** merged. If both are specified, only $content is used.
     *
     * @param string $url        URL
     * @param string $user       User name for basic authentication
     * @param string $password   User password for basic authentication
     * @param array  $headers    HTTP headers
     * @param array  $getParams
     * @param array  $postParams the POST parameters
     * @param null   $content
     * @return Response
     * @throws HTTPStatus502Exception on malformed URL
     */
    public function request($url,
                            $user = null,
                            $password = null,
                            $headers = array(),
                            $getParams = array(),
                            $postParams = array(),
                            $content = null)
    {
        $configuration = $this->container->getParameter("owsproxy.proxy");
        $proxy         = new CommonProxy($configuration, ProxyQuery::createFromUrl(
            $url,
            $user,
            $password,
            $headers, $getParams, $postParams,
            $content));
        // upstream annotation is wrong, Buzz always returns a Response object from a request.
        // upstream annotation is also missing the fact that nothing (=null aka void) is returned if method is neither
        // GET nor POST. But createFromUrl will guarantee either GET or POST, so that's ok here.
        /** @var Response $response */
        $response = $proxy->handle();
        return $response;
    }
}
