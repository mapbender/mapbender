<?php

namespace FOM\ManagerBundle\Configuration;

use Symfony\Component\Routing\Annotation\Route as BaseRoute;

/**
 * Route annotation for Manager Controllers.
 *
 * This is a trivial subclass of the regular Symfony Route annotation
 * All the magic with route prefixing happens in FOM\ManagerBundle\Routing\AnnotatedRouteControllerLoader.
 *
 * @Annotation
 * @author Christian Wygoda
 */
class Route extends BaseRoute
{
}

