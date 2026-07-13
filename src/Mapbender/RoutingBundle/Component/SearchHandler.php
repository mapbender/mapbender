<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use Mapbender\RoutingBundle\Component\SearchDriver\SolrDriver;
use Mapbender\RoutingBundle\Component\SearchDriver\SqlDriver;

class SearchHandler {

    public function __construct(protected SolrDriver $solrDriver, protected SqlDriver $sqlDriver)
    {
    }

    public function search($requestParams, array $configuration): JsonResponse
    {
        $driver = (!empty($configuration['searchConfig']['driver'])) ? $configuration['searchConfig']['driver'] : false;
        $searchConfig = (!empty($configuration['searchConfig'][$driver])) ? $configuration['searchConfig'][$driver] : false;

        if ($configuration['useSearch'] && $searchConfig) {
            $response = match ($driver) {
                'solr' => $this->solrDriver->search($requestParams, $searchConfig),
                'sql' => $this->sqlDriver->search($requestParams, $searchConfig),
                default => throw new Exception('Unsupported Driver'),
            };
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
