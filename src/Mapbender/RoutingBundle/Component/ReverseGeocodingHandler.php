<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Component\HttpFoundation\JsonResponse;
use Mapbender\RoutingBundle\Component\ReverseGeocodingDriver\SqlDriver;

class ReverseGeocodingHandler {

    protected SqlDriver $sqlDriver;

    public function __construct(SqlDriver $sqlDriver) {
        $this->sqlDriver = $sqlDriver;
    }

    public function doReverseGeocoding ($requestParams, $configuration): JsonResponse
    {
        // @todo Implement reverse Geocoding

        return new JsonResponse();
    }
}
