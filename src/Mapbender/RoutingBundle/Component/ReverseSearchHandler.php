<?php

namespace Mapbender\RoutingBundle\Component;


use Doctrine\DBAL\DBALException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReverseSearchHandler extends RequestHandler {


    /**
     * @param array $configuration
     * @param ContainerInterface $container
     * @return Response
     * @throws DBALException
     */
    public function getAction(array $configuration, ContainerInterface $container)
    {
        $request = $container->get('request');
        $requestParams = $request->query->all();

        $configuration = $this->getRevGeocodeConfiguration($configuration);

        $conn = $configuration['connection'];
        $connection = $container->get('doctrine.dbal.' . $conn . '_connection');
        $driver = new RevGeocodeDriver($configuration,$requestParams,$connection);
        $responseData = $driver->getRevGeoCodeResult();

        if (count($responseData)==0){
            $responseData = array(
                    'code' => 500,
                    'apiMessage' => 'SearchDriver is not valid',
                    'messageDetails' => 'no Data',
            );
            $statusCode = 500;
        } else {
           $statusCode = 200;
        }
        return new JsonResponse($responseData, $statusCode, array('Content-Type', 'application/json'));
    }


    /**
     * Get Configuration from BackenedAdmintype, validation and create HttpActionTemplate
     * @param $defaultConfiguration
     * @return array
     */
    public function getRevGeocodeConfiguration($defaultConfiguration): ?array
    {
        $configuration = $defaultConfiguration['reverse'];


        # Search the admintype of the item for the Search-Config
        # If the search config of the admin type has ever been defined, then it takes the settings
        if ( count($configuration) > 0 && $defaultConfiguration['addReverseGeocoding'] == true){


            switch ($configuration['revGeocodingDriver']){
                case 'sql':

                    return array(
                        'connection' => $configuration['revGeoConnection'],
                        'tableName' => $configuration['revTableName'],
                        'geometryColumn' => $configuration['revRowGeoWay'],
                        'searchColumn' => $configuration['revRowSearch'],
                        'searchBuffer' => $configuration['revSearchBuffer']
                    );
                    break;
            }
        }
        return null;

    }



}
