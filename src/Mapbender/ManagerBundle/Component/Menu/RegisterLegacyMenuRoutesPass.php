<?php /** @noinspection PhpMissingParentConstructorInspection */


namespace Mapbender\ManagerBundle\Component\Menu;


use Mapbender\ManagerBundle\Component\ManagerBundle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\IdentityTranslator;

class RegisterLegacyMenuRoutesPass extends RegisterMenuRoutesPass
{
    /** @var KernelInterface */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function process(ContainerBuilder $container)
    {
        $legacyBundleKey = 'mapbender.manager.menu.legacy_bundles';
        $legacyBundleNames = $container->getParameter($legacyBundleKey);
        $dummyContainer = new Container();
        $dummyContainer->set('translator', new IdentityTranslator());
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof ManagerBundle) {
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
