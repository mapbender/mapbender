<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

class PgRoutingDriver extends RoutingDriver
{
    protected DoctrineRegistry $doctrine;

    public function __construct(DoctrineRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
