<?php

namespace FOM\ManagerBundle\Routing;

use Symfony\Component\Routing\Route;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader as FrameworkAnnotatedRouteControllerLoader;

/**
 * Prefixes manager routes with a configurable (fom_manager.route_prefix) prefix
 */
class AnnotatedRouteControllerLoader extends FrameworkAnnotatedRouteControllerLoader
{

    public function __construct(protected string $prefix)
    {
        parent::__construct();
    }

    /**
     * For all route annotations using
     * FOM\ManagerBundle\Configuration\Route,
     * this adds the configured prefix.
     */
    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot)
    {
        parent::configureRoute($route, $class, $method, $annot);
        if(is_a($annot, ManagerRoute::class)) {
            $route->setPath($this->prefix . $route->getPath());
        }
    }
}
