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
        foreach ($container->findTaggedServiceIds('mapbender.element') as $id => $tagInfo) {
            $definition = $container->getDefinition($id);
            $handledClassNames = array($definition->getClass());
            foreach ($tagInfo as $attributes) {
                if (!empty($attributes['replaces'])) {
                    $handledClassNames = array_merge($handledClassNames, explode(',', $attributes['replaces']));
                }
            }
            /** @see \Mapbender\CoreBundle\Component\ElementInventoryService::registerService */
            $inventoryDefinition->addMethodCall('registerService', array(new Reference($id), $handledClassNames));
        }
    }
}
