<?php


namespace Mapbender\CoreBundle\DependencyInjection\Compiler;


use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Initializes the combined inventory of all Mapbender Elements advertised by all active
 * MapbenderBundles.
 * Because the kernel is not available to compiler passes initialized from a bundle, this
 * pass is actually initialized by the BaseKernel itself.
 *
 * @see ElementInventoryService::setInventory()
 * @see BaseKernel::buildContainer()
 */
class RebuildElementInventoryPass implements CompilerPassInterface
{
    /** @var KernelInterface */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function process(ContainerBuilder $container)
    {
        $allElementClasses = $this->collectElementClassNames($container);
        $inventoryDefinition = $container->getDefinition('mapbender.element_inventory.service');
        $inventoryDefinition->addMethodCall('setInventory', array($allElementClasses));
    }

    protected function collectElementClassNames(ContainerBuilder $container)
    {
        $classNameLists = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof MapbenderBundle) {
                // Bundle may not have a container at this stage but may need it for
                // configuration access (e.g. see MapbenderWmsBundle)
                $bundle->setContainer($container);
                $classNameLists[] = $bundle->getElements() ?: array();
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
