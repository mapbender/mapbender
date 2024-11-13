<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TriasDriver extends RoutingDriver
{
    protected HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getRoute($requestParams, $configuration): array
    {
        // TODO: Implement getRoute() method.
    }

    public function processResponse($response, $config)
    {
        // TODO: Implement processResponse() method.
    }
}
