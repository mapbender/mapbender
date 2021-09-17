<?php

namespace FOM\ManagerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Collects controllers attached to the manager interface from the bundles.
 *
 * @author Christian Wygoda
 */
class RouteAnnotationsPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        if($container->hasDefinition('sensio_framework_extra.routing.loader.annot_class')) {
            $definition = $container->getDefinition('sensio_framework_extra.routing.loader.annot_class');
            $definition->setClass('FOM\ManagerBundle\Routing\AnnotatedRouteControllerLoader');
            $definition->addArgument($container->getParameter('fom_manager.route_prefix'));
        }
    }
}

