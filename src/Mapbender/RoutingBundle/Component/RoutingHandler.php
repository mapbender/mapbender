<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Mapbender\RoutingBundle\Component\Driver\GraphhopperDriver;
use Mapbender\RoutingBundle\Component\Driver\OsrmDriver;
use Mapbender\RoutingBundle\Component\Driver\PgRoutingDriver;
use \Exception;

class RoutingHandler {

    protected OsrmDriver $osrmDriver;

    public function __construct(OsrmDriver $osrmDriver) {
        $this->osrmDriver = $osrmDriver;
    }

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
                $route = new GraphhopperDriver($configuration["backendConfig"]["graphhopper"],$requestParams,$locale,$translator);
                break;
            case 'pgrouting' :
                $conn = $configuration["backendConfig"]["pgrouting"]["connection"];
                /**
                 * @var Connection $connection
                 */
                $connection = $container->get('doctrine.dbal.' . $conn . '_connection');
                $route = new PgRoutingDriver($configuration["backendConfig"]["pgrouting"],$requestParams,$locale,$translator,$connection);
                break;
            default:
                throw new Exception('No Routing Driver selected.');
        }

        return new JsonResponse($route, 200);
    }
}
