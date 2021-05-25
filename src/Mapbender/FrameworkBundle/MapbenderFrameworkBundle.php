<?php


namespace Mapbender\FrameworkBundle;


use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterElementServicesPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;


class MapbenderFrameworkBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');
        // Register service elements
        // Run pass with reduced priority, so it happens after non-service inventory building has completed
        $container->addCompilerPass(new RegisterElementServicesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
    }

    public function getContainerExtension()
    {
        return null;
    }
}
