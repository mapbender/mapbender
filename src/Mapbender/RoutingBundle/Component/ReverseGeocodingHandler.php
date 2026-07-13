<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Component\HttpFoundation\JsonResponse;
use Mapbender\RoutingBundle\Component\ReverseGeocodingDriver\SqlDriver;

class ReverseGeocodingHandler {

    public function __construct(protected SqlDriver $sqlDriver)
    {
    }

    public function doReverseGeocoding ($requestParams, $configuration): JsonResponse
    {
        // @todo Implement reverse Geocoding

        return new JsonResponse();
    }
}
