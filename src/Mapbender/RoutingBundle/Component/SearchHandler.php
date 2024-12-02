<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use Mapbender\RoutingBundle\Component\SearchDriver\SolrDriver;
use Mapbender\RoutingBundle\Component\SearchDriver\SqlDriver;

class SearchHandler {

    protected SolrDriver $solrDriver;

    protected SqlDriver $sqlDriver;

    public function __construct(SolrDriver $solrDriver, SqlDriver $sqlDriver) {
        $this->solrDriver = $solrDriver;
        $this->sqlDriver = $sqlDriver;
    }

    public function search($requestParams, $configuration): JsonResponse
    {
        $driver = (!empty($configuration['searchConfig']['driver'])) ? $configuration['searchConfig']['driver'] : false;
        $searchConfig = (!empty($configuration['searchConfig'][$driver])) ? $configuration['searchConfig'][$driver] : false;

        if ($configuration['useSearch'] && $searchConfig) {
            switch ($driver) {
                case 'solr';
                    $response = $this->solrDriver->search($requestParams, $searchConfig);
                    break;
                case 'sql';
                    $response = $this->sqlDriver->search($requestParams, $searchConfig);
                    break;
                default:
                    throw new Exception('Unsupported Driver');
            }
        }

        return new JsonResponse($response, 200);
    }
}
