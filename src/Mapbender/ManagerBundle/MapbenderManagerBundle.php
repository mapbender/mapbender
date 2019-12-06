<?php

namespace Mapbender\ManagerBundle;

use Mapbender\ManagerBundle\Component\ManagerBundle;
use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Mapbender\ManagerBundle\Component\Menu\RegisterLegacyMenuRoutesPass;
use Mapbender\ManagerBundle\Component\Menu\RegisterMenuRoutesPass;
use Mapbender\ManagerBundle\DependencyInjection\Compiler\FinalizeMenuPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class MapbenderManagerBundle extends ManagerBundle
{

    public function build(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');

        $this->addMenu($container);
        $container->addCompilerPass(new RegisterLegacyMenuRoutesPass());
    }

    protected function addMenu(ContainerBuilder $container)
    {
        $appMenu = MenuItem::create("mb.manager.managerbundle.applications", 'mapbender_manager_application_index')
            ->setWeight(10)
            ->addChildren(array(
                MenuItem::create('mb.manager.managerbundle.new_application', 'mapbender_manager_application_new')
                    ->requireEntityGrant('Mapbender\CoreBundle\Entity\Application', 'CREATE'),
                MenuItem::create('mb.manager.managerbundle.export_application', 'mapbender_manager_application_export')
                    ->requireEntityGrant('Mapbender\CoreBundle\Entity\Application', 'CREATE'),
                MenuItem::create('mb.manager.managerbundle.import_application', 'mapbender_manager_application_import')
                    ->requireEntityGrant('Mapbender\CoreBundle\Entity\Application', 'CREATE'),
            ))
        ;
        $sourceMenu = MenuItem::create('mb.manager.managerbundle.sources', 'mapbender_manager_repository_index')
            ->requireEntityGrant('Mapbender\CoreBundle\Entity\Source', 'VIEW')
            ->setWeight(20)
            ->addChildren(array(
                MenuItem::create('mb.manager.managerbundle.add_source', 'mapbender_manager_repository_new')
                    ->requireEntityGrant('Mapbender\CoreBundle\Entity\Source', 'CREATE'),
            ))
        ;

        $container->addCompilerPass(new RegisterMenuRoutesPass($appMenu));
        $container->addCompilerPass(new RegisterMenuRoutesPass($sourceMenu));

        // NOTE: TYPE_AFTER_REMOVING is the final phase of the container build.
        // The default TYPE_BEFORE_OPTIMIZATION is the very first phase where passes can be
        // registered. We could use any other phase here except for the default. The only
        // thing we care about is that this pass happens after any potential menu
        // registration / removal passes.
        $container->addCompilerPass(new FinalizeMenuPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
