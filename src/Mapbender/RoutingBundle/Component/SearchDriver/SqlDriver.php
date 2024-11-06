<?php

namespace Mapbender\RoutingBundle\Component\SearchDriver;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SqlDriver
{
    protected HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function search($requestParams, $searchConfig)
    {
        # @todo implement sql autocomplete search here
    }
}
