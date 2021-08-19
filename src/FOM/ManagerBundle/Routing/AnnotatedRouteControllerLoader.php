<?php

namespace FOM\ManagerBundle\Routing;

use Symfony\Component\Routing\Route;
use Doctrine\Common\Annotations\Reader;

/**
 * Route annotation loader
 *
 * This loader extends the one in the
 * FrameworkExtraBundle by prefixing manager
 * routes with a configurable prefix.
 *
 * @author Christian Wygoda
 */
class AnnotatedRouteControllerLoader extends \Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader
{
    protected $prefix;

    public function __construct(Reader $reader, $prefix)
    {
        parent::__construct($reader);
        $this->prefix = $prefix;
    }

    /**
     * For all route annotations using
     * FOM\ManagerBundle\Configuration\Route,
     * this adds the configured prefix.
     *
     * @inheritdoc
     */
    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot)
    {
        parent::configureRoute($route, $class, $method, $annot);
        if(is_a($annot, 'FOM\ManagerBundle\Configuration\Route')) {
            $route->setPath($this->prefix . $route->getPath());
        }
    }
}
