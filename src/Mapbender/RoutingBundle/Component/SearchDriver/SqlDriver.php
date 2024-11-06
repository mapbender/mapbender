<?php

namespace Mapbender\RoutingBundle\Component\SearchDriver;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

class SqlDriver
{
    protected DoctrineRegistry $doctrine;

    public function __construct(DoctrineRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function search($requestParams, $searchConfig)
    {
        # @todo implement sql autocomplete search here
    }
}
