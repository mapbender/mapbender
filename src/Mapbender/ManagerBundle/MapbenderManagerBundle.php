<?php

namespace Mapbender\ManagerBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\DependencyInjection\Compiler\MapbenderYamlCompilerPass;
use Mapbender\ManagerBundle\Component\Menu\ApplicationItem;
use Mapbender\ManagerBundle\Component\Menu\RegisterMenuRoutesPass;
use Mapbender\ManagerBundle\Component\Menu\SourceCreationItem;
use Mapbender\ManagerBundle\Component\Menu\TopLevelItem;
use Mapbender\ManagerBundle\DependencyInjection\Compiler\FinalizeMenuPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MapbenderManagerBundle extends MapbenderBundle
{

    public function build(ContainerBuilder $container)
    {
        $appFileDir = dirname(__FILE__) . '/Resources/config/applications';
        $container->addCompilerPass(new MapbenderYamlCompilerPass(realpath($appFileDir)));
        $this->addMenu($container);
    }

    protected function addMenu(ContainerBuilder $container)
    {
        $appMenu = TopLevelItem::create("mb.manager.managerbundle.applications", 'mapbender_manager_application_index')
            ->setWeight(10)
            ->addChildren(array(
                ApplicationItem::create('mb.manager.managerbundle.new_application', 'mapbender_manager_application_new'),
                ApplicationItem::create('mb.manager.managerbundle.export_application', 'mapbender_manager_application_export'),
                ApplicationItem::create('mb.manager.managerbundle.import_application', 'mapbender_manager_application_import'),
            ))
        ;
        $sourceMenu = TopLevelItem::create('mb.manager.managerbundle.sources', 'mapbender_manager_repository_index')
            ->setWeight(20)
            ->addChildren(array(
                new SourceCreationItem()
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

    public function getRoles()
    {
        return array(
            'ROLE_ADMIN_MAPBENDER_APPLICATION'
            => 'Can administrate applications');
    }

}
