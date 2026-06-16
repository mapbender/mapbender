<?php

namespace FOM\ManagerBundle\Routing;

use Symfony\Component\Routing\Route;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader as FrameworkAttributeRouteControllerLoader;

/**
 * Prefixes manager routes with a configurable (fom_manager.route_prefix) prefix
 */
class AnnotatedRouteControllerLoader extends FrameworkAttributeRouteControllerLoader
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
    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, object $attr): void
    {
        parent::configureRoute($route, $class, $method, $attr);
        if(is_a($attr, ManagerRoute::class)) {
            $route->setPath($this->prefix . $route->getPath());
        }
    }
}
