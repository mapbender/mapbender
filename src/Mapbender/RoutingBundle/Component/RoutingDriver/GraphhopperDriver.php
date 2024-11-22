<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Mapbender\Component\Transport\HttpTransportInterface;

class GraphhopperDriver extends RoutingDriver
{
    protected HttpTransportInterface $httpTransport;

    public function __construct(HttpTransportInterface $httpTransport)
    {
        $this->httpTransport = $httpTransport;
    }

    public function getRoute($requestParams, $configuration): array
    {
        // TODO: Implement getRoute() method.
    }

    public function processResponse($response, $configuration)
    {
        // TODO: Implement processResponse() method.
    }
}
