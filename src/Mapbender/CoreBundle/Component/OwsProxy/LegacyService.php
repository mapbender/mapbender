<?php

namespace Mapbender\CoreBundle\Component\OwsProxy;

use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Legacy "owsproxy3"-based http client.
 * All requests return BuzzMessage
 *
 * @deprecated
 * @internal
 *
 * Absorbed from owsproxy3 repository, where it will be reverted.
 * @see https://github.com/mapbender/owsproxy3/compare/f7a3dc86ac0eac4896e55a577c5416814a491f11...65b66009417aca618235ec4c76100d2bb4399dac
 *
 *
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
        $this->setContainer($this->container);
    }

    /**
     * /**
     * Creates an instance from parameters
     *
     * @param string $url        URL
     * @param string $user       User name for basic authentication
     * @param string $password   User password for basic authentication
     * @param array  $headers    HTTP headers
     * @param array  $getParams
     * @param array  $postParams the POST parameters
     * @param null   $content
     * @return \Buzz\Message\Response|null
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
        return $proxy->handle();
    }
}
