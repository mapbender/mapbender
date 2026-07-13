<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Mapbender\Component\Transport\HttpTransportInterface;

class TriasDriver extends RoutingDriver
{
    public function __construct(protected HttpTransportInterface $httpTransport)
    {
    }

    public function getRoute($requestParams, $configuration): array
    {
        // TODO: Implement getRoute() method.
    }

    public function processResponse($response, $configuration): void
    {
        // TODO: Implement processResponse() method.
    }
}
