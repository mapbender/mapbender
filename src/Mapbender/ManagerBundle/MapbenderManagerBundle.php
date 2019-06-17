<?php

namespace Mapbender\ManagerBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\DependencyInjection\Compiler\MapbenderYamlCompilerPass;
use Mapbender\ManagerBundle\Component\Menu\ApplicationItem;
use Mapbender\ManagerBundle\Component\Menu\SourceCreationItem;
use Mapbender\ManagerBundle\Component\Menu\TopLevelItem;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MapbenderManagerBundle extends MapbenderBundle
{

    public function build(ContainerBuilder $container)
    {
        $appFileDir = dirname(__FILE__) . '/Resources/config/applications';
        $container->addCompilerPass(new MapbenderYamlCompilerPass(realpath($appFileDir)));
    }

    public function getManagerControllers()
    {
        return array(
            TopLevelItem::create("mb.manager.managerbundle.applications", 'mapbender_manager_application_index')
                ->setWeight(10)
                ->addChildren(array(
                    ApplicationItem::create('mb.manager.managerbundle.new_application', 'mapbender_manager_application_new'),
                    ApplicationItem::create('mb.manager.managerbundle.export_application', 'mapbender_manager_application_export'),
                    ApplicationItem::create('mb.manager.managerbundle.import_application', 'mapbender_manager_application_import'),
                )),
            TopLevelItem::create('mb.manager.managerbundle.sources', 'mapbender_manager_repository_index')
                ->setWeight(20)
                ->addChildren(array(
                    new SourceCreationItem()
                )),
        );
    }

    public function getRoles()
    {
        return array(
            'ROLE_ADMIN_MAPBENDER_APPLICATION'
            => 'Can administrate applications');
    }

}
