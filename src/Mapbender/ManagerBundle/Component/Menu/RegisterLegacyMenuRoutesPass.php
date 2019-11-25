<?php


namespace Mapbender\ManagerBundle\Component\Menu;


use Mapbender\ManagerBundle\Component\ManagerBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * @deprecated remove in v3.1
 */
class RegisterLegacyMenuRoutesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $legacyBundleKey = 'mapbender.manager.menu.legacy_bundles';
        $legacyBundleNames = $container->getParameter($legacyBundleKey);
        $dummyContainer = new Container();
        $dummyContainer->set('translator', new IdentityTranslator());
        foreach ($container->getParameter('kernel.bundles') as $bundleFqcn) {
            if (\is_a($bundleFqcn, 'Mapbender\ManagerBundle\Component\ManagerBundle', true)) {
                /** @var ManagerBundle $bundle */
                $bundle = new $bundleFqcn();
                $bundle->setContainer($dummyContainer);
                $menuDefinition = $bundle->getManagerControllers();
                if ($menuDefinition) {
                    @trigger_error("Deprecated: Bundle " . get_class($bundle) . " uses legacy getManagerControllers implementation to plug itself into the menu. Please use a compiler pass. See MapbenderManagerBundle::addMenu", E_USER_DEPRECATED);
                    $legacyBundleNames[] = $bundle->getName();
                }
            }
        }
        $container->setParameter($legacyBundleKey, $legacyBundleNames);
    }
}
