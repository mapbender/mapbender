<?php


namespace Mapbender\CoreBundle\DependencyInjection\Compiler;


use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Initializes Element inventory from legacy MapbenderBundle::getElements()
 *
 * @see ElementInventoryService::setInventory()
 * @deprecated use services tagged with 'mapbender.element'
 */
class RebuildElementInventoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $allElementClasses = $this->collectElementClassNames($container);
        $inventoryDefinition = $container->getDefinition('mapbender.element_inventory.service');
        $inventoryDefinition->addMethodCall('setInventory', array($allElementClasses));
    }

    protected function collectElementClassNames(ContainerBuilder $container)
    {
        $classNameLists = array();
        foreach ($container->getParameter('kernel.bundles') as $bundleFqcn) {
            if (\is_a($bundleFqcn, 'Mapbender\CoreBundle\Component\MapbenderBundle', true)) {
                /** @var MapbenderBundle $bundle */
                $bundle = new $bundleFqcn();
                // Bundle may not have a container at this stage but may need it for
                // configuration access (e.g. see MapbenderWmsBundle)
                $bundle->setContainer($container);
                $classNameLists[] = $bundle->getElements() ?: array();
            } else {
                $bundle = null;
            }
        }
        if ($classNameLists) {
            $allClassNames = \call_user_func_array('array_merge', $classNameLists);
            return array_unique(array_values($allClassNames));
        } else {
            return array();
        }
    }
}
