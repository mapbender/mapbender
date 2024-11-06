<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use \Exception;
use Mapbender\RoutingBundle\Component\RoutingDriver\OsrmDriver;
use Mapbender\RoutingBundle\Component\RoutingDriver\GraphhopperDriver;
use Mapbender\RoutingBundle\Component\RoutingDriver\PgRoutingDriver;
use Mapbender\RoutingBundle\Component\RoutingDriver\TriasDriver;

class RoutingHandler {

    protected OsrmDriver $osrmDriver;

    protected GraphhopperDriver $graphhopperDriver;

    protected PgRoutingDriver $pgRoutingDriver;

    protected TriasDriver $triasDriver;

    public function __construct(OsrmDriver $osrmDriver, GraphhopperDriver $graphhopperDriver, PgRoutingDriver $pgRoutingDriver, TriasDriver $triasDriver) {
        $this->osrmDriver = $osrmDriver;
        $this->graphhopperDriver = $graphhopperDriver;
        $this->pgRoutingDriver = $pgRoutingDriver;
        $this->triasDriver = $triasDriver;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function calculateRoute($requestParams, $configuration): JsonResponse
    {
        $driver = $configuration['routingDriver'];
        // $locale = $request->getLocale();
        // $translator = $container->get('translator');

        switch ($driver) {
            case 'osrm':
                $route = $this->osrmDriver->getRoute($requestParams, $configuration);
                break;
            case 'graphhopper':
                $route = $this->graphhopperDriver->getRoute($requestParams, $configuration);
                break;
            case 'pgrouting' :
                $route = $this->pgRoutingDriver->getRoute($requestParams, $configuration);
                break;
            case 'trias' :
                $route = $this->triasDriver->getRoute($requestParams, $configuration);
                break;
            default:
                throw new Exception('No Routing Driver selected.');
        }

        return new JsonResponse($route, 200);
    }
}
