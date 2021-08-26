<?php

namespace Mapbender\Component\Transport;

use OwsProxy3\CoreBundle\Component\HttpFoundationClient;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation for service id mapbender.http_transport.service
 * @since v3.0.8-beta1
 */
class OwsProxyTransport implements HttpTransportInterface
{
    /** @var HttpFoundationClient */
    protected $client;

    public function __construct(HttpFoundationClient $client)
    {
        $this->client = $client;
    }

    /**
     * Fetch $url via GET and return a Response object.
     *
     * @param string $url
     * @return Response
     */
    public function getUrl($url)
    {
        return $this->client->handleQuery(ProxyQuery::createGet($url));
    }
}
