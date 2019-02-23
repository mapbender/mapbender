<?php

namespace Mapbender\Component\Transport;

use Mapbender\CoreBundle\Utils\UrlUtil;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class OwsProxyTransport implements HttpTransportInterface
{
    /** @var mixed[] */
    protected $proxyConfig;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct($proxyConfig, LoggerInterface $logger)
    {
        $this->proxyConfig = $proxyConfig;
        $this->logger = $logger;
    }

    /**
     * Fetch $url via GET and return a Response object.
     *
     * @param string $url
     * @return Response
     */
    public function getUrl($url)
    {
        // ProxyQuery::getGetUrl shreds user and password encoding if credentials are in url.
        // work around this for the time being...
        $parts = parse_url($url);
        if (!empty($parts['user'])) {
            $user = urldecode($parts['user']);
        } else {
            $user = null;
        }
        if (!empty($parts['pass'])) {
            $pass = urldecode($parts['pass']);
        } else {
            $pass = null;
        }
        if ($user || $pass) {
            unset($parts['user']);
            unset($parts['pass']);
            $safeUrl = UrlUtil::reconstructFromParts($parts);
        } else {
            $safeUrl = $url;
        }
        $proxyQuery = ProxyQuery::createFromUrl($safeUrl, $user, $pass);
        $proxy = new CommonProxy($this->proxyConfig, $proxyQuery, $this->logger);
        $buzzResponse = $proxy->handle();
        return $this->convertBuzzResponse($buzzResponse);
    }

    /**
     * Convert a Buzz Response to a Symfony HttpFoundation Response
     *
     * @todo: This belongs in owsproxy; it's the only part of Mapbender that uses Buzz
     *
     * @param \Buzz\Message\Response $buzzResponse
     * @return Response
     */
    public static function convertBuzzResponse($buzzResponse)
    {
        // adapt header formatting: Buzz uses a flat list of lines, HttpFoundation expects a name: value mapping
        $headers = array();
        foreach ($buzzResponse->getHeaders() as $headerLine) {
            $parts = explode(':', $headerLine, 2);
            if (count($parts) == 2 && false === stripos($parts[0], 'Transfer-Encoding')) {
                $headers[$parts[0]] = $parts[1];
            }
        }
        $response = new Response($buzzResponse->getContent(), $buzzResponse->getStatusCode(), $headers);
        $response->setProtocolVersion($buzzResponse->getProtocolVersion());
        $statusText = $buzzResponse->getReasonPhrase();
        if ($statusText) {
            $response->setStatusCode($buzzResponse->getStatusCode(), $statusText);
        }
        return $response;
    }
}
