<?php


namespace Mapbender\FrameworkBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterElementServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $inventoryDefinition = $container->getDefinition('mapbender.element_inventory.service');
        $registering = array();
        foreach ($container->findTaggedServiceIds('mapbender.element') as $id => $tagInfo) {
            $definition = $container->getDefinition($id);
            $canonical = $definition->getClass();
            $handledClassNames = array($canonical);
            foreach ($tagInfo as $attributes) {
                if (!empty($attributes['replaces'])) {
                    $handledClassNames = array_merge($handledClassNames, explode(',', $attributes['replaces']));
                }
                if (!empty($attributes['canonical'])) {
                    $canonical = $attributes['canonical'];
                    $handledClassNames[] = $canonical;
                }
            }
            $handledClassNames = array_unique($handledClassNames);
            $registering[] = array(new Reference($id), $handledClassNames, $canonical);
        }
        /** @see \Mapbender\CoreBundle\Component\ElementInventoryService::registerServices */
        $inventoryDefinition->addMethodCall('registerServices', array($registering));
    }
}
