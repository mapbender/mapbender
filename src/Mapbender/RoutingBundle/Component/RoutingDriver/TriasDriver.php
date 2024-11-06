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

    public function getRoute($requestParams, $configuration)
    {
        // TODO: Implement getRoute() method.
    }

    public function getResponse(): array
    {
        // TODO: Implement getResponse() method.
    }

    public function processResponse($response)
    {
        // TODO: Implement processResponse() method.
    }
}
