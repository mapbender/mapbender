<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

class PgRoutingDriver extends RoutingDriver
{
    public function __construct(protected DoctrineRegistry $doctrine)
    {
    }

    public function getRoute($requestParams, $configuration): array
    {
        // TODO: Implement getRoute() method.
    }

    public function processResponse($response, $config): void
    {
        // TODO: Implement processResponse() method.
    }
}
