<?php

namespace Mapbender\RoutingBundle\Component;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class RequestHandler {

    /**
     * @param array $configuration
     * @param ContainerInterface $container
     * @return Response
     */
    abstract function getAction(array $configuration, ContainerInterface $container);


}
