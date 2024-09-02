<?php

namespace Mapbender\RoutingBundle\Component;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use FOM\UserBundle\Component\Ldap\Exception;
use Mapbender\RoutingBundle\Component\Driver\GraphhopperDriver;
use Mapbender\RoutingBundle\Component\Driver\OSRMDriver;
use Mapbender\RoutingBundle\Component\Driver\PgRoutingDriver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class RoutingHandler extends RequestHandler {


    /**
     * Returns the result of a route action
     *
     * @param array $configuration
     * @param ContainerInterface $container
     * @return Response
     * @throws DBALException
     */
    public function getAction(array $configuration, ContainerInterface $container)
    {

        $request = $container->get('request');
        $locale = $request->getLocale();

        $requestParams = $request->request->all();
        $driverName = $configuration['routingDriver'];
        /**
         * @var TranslatorInterface $translator
         */
        $translator = $container->get('translator');

        switch($driverName) {
            case 'graphhopper':
                $routingDriver = new GraphhopperDriver($configuration["backendConfig"]["graphhopper"],$requestParams,$locale,$translator);
                break;
            case 'osrm' :
                $routingDriver = new OSRMDriver($configuration["backendConfig"]["osrm"],$requestParams,$locale,$translator);
                break;
            case 'pgrouting' :
                $conn = $configuration["backendConfig"]["pgrouting"]["connection"];
                /**
                 * @var Connection $connection
                 */
                $connection = $container->get('doctrine.dbal.' . $conn . '_connection');
                $routingDriver = new PgRoutingDriver($configuration["backendConfig"]["pgrouting"],$requestParams,$locale,$translator,$connection);
                break;
            default:
                throw new Exception("No Routing Driver selected");
        }

        $rawResponse = $routingDriver->getResponse();
        // Add error handling here
        if ($rawResponse["responseCode"] == 200) {
            $responseData = json_decode($rawResponse['responseData'], true);
            $response = array("routeData" => $routingDriver->processResponse($responseData));
        } else {
            throw new Exception($rawResponse["responseData"]);
        }
        $jsonResponse = new JsonResponse($response, 200, array('Content-Type', 'application/json') );

        return $jsonResponse;
    }

}
