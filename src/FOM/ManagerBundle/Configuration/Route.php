<?php

namespace FOM\ManagerBundle\Configuration;

use Symfony\Component\Routing\Attribute\Route as BaseRoute;

/**
 * Route annotation for Manager Controllers.
 *
 * This is a trivial subclass of the regular Symfony Route annotation
 * All the magic with route prefixing happens in @see \FOM\ManagerBundle\Routing\AnnotatedRouteControllerLoader.
 *
 * @Annotation
 * @author Christian Wygoda
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route extends BaseRoute
{
}

