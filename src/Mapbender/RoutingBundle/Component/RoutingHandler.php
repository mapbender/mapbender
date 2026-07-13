<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Component\HttpFoundation\Response;
use Mapbender\Component\Transport\ConnectionErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Exception;
use Mapbender\RoutingBundle\Component\RoutingDriver\OsrmDriver;
use Mapbender\RoutingBundle\Component\RoutingDriver\GraphhopperDriver;
use Mapbender\RoutingBundle\Component\RoutingDriver\PgRoutingDriver;
use Mapbender\RoutingBundle\Component\RoutingDriver\TriasDriver;

class RoutingHandler {

    public function __construct(protected OsrmDriver $osrmDriver, protected GraphhopperDriver $graphhopperDriver, protected PgRoutingDriver $pgRoutingDriver, protected TriasDriver $triasDriver)
    {
    }

    /**
     * @throws ConnectionErrorException
     */
    public function calculateRoute($requestParams, array $configuration): JsonResponse
    {
        $driver = $configuration['routingDriver'];

        $route = match ($driver) {
            'osrm' => $this->osrmDriver->getRoute($requestParams, $configuration),
            'graphhopper' => $this->graphhopperDriver->getRoute($requestParams, $configuration),
            'pgrouting' => $this->pgRoutingDriver->getRoute($requestParams, $configuration),
            'trias' => $this->triasDriver->getRoute($requestParams, $configuration),
            default => throw new Exception('No Routing Driver selected.'),
        };

        return new JsonResponse($route, Response::HTTP_OK);
    }
}
