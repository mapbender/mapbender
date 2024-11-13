<?php

namespace Mapbender\RoutingBundle\Component\ReverseGeocodingDriver;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

class SqlDriver
{
    protected DoctrineRegistry $doctrine;

    public function __construct(DoctrineRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
}
