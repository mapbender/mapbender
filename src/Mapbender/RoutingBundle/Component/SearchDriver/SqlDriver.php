<?php

namespace Mapbender\RoutingBundle\Component\SearchDriver;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

class SqlDriver
{
    public function __construct(protected DoctrineRegistry $doctrine)
    {
    }

    public function search($requestParams, $searchConfig): void
    {
        # @todo implement sql autocomplete search here
    }
}
