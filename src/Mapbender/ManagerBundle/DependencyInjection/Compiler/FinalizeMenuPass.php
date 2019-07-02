<?php


namespace Mapbender\ManagerBundle\DependencyInjection\Compiler;


use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FinalizeMenuPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $itemsKey = 'mapbender.manager.menu.items';
        /** @var MenuItem[] $items */
        $items = array_map('\unserialize', $container->getParameter($itemsKey));
        $routeBlacklist = $container->getParameter('mapbender.manager.menu.route_prefix_blacklist');
        $items = MenuItem::filterBlacklistedRoutes($items, $routeBlacklist);
        $items = MenuItem::sortItems($items);
        // serialize remaining items again, place back into container
        $itemsValue = array_map('\serialize', array_values($items));
        $container->setParameter($itemsKey, $itemsValue);
        // Too late in container build lifecycle for menu extension to automatically pick
        // up the updated container param.
        // => Replace constructor argument explicitly.
        $menuExtensionDefinition = $container->getDefinition('mapbender.twig.manager.menu');
        $menuExtensionDefinition->replaceArgument(0, $itemsValue);
    }
}
