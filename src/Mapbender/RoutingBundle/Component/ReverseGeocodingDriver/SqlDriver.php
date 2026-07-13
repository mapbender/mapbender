<?php

namespace Mapbender\RoutingBundle\Component\ReverseGeocodingDriver;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

class SqlDriver
{
    public function __construct(protected DoctrineRegistry $doctrine)
    {
    }
}
