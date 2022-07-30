<?php

namespace Mapbender\ManagerBundle;

use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Mapbender\ManagerBundle\Component\Menu\RegisterMenuRoutesPass;
use Mapbender\ManagerBundle\Component\Menu\SourceMenu;
use Mapbender\ManagerBundle\DependencyInjection\Compiler\FinalizeMenuPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MapbenderManagerBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');
        $container->addResource(new FileResource($loader->getLocator()->locate('services.xml')));
        $loader->load('controllers.xml');
        $container->addResource(new FileResource($loader->getLocator()->locate('controllers.xml')));

        $this->addMenu($container);
    }

    protected function addMenu(ContainerBuilder $container)
    {
        $appMenu = MenuItem::create("mb.terms.application.plural", 'mapbender_core_welcome_list')
            ->setWeight(10)
        ;
        $sourceMenu = SourceMenu::create('mb.terms.source.plural', 'mapbender_manager_repository_index')
            ->setWeight(20)
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
