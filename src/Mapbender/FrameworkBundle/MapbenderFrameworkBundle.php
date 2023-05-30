<?php


namespace Mapbender\FrameworkBundle;


use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterApplicationTemplatesPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterElementServicesPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterIconPackagesPass;
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
        $loader->load('symfony.xml');
        // Register service elements
        // Run pass with reduced priority, so it happens after non-service inventory building has completed
        $container->addCompilerPass(new RegisterElementServicesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
        // Forward available application template classes to registry service
        /** @see \Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry */
        $container->addCompilerPass(new RegisterApplicationTemplatesPass('mapbender.application_template_registry'));
        // Forward available icon packages to icon index
        /** @see \Mapbender\FrameworkBundle\Component\IconIndex */
        $container->addCompilerPass(new RegisterIconPackagesPass('mapbender.icon_index'));
    }

    public function getContainerExtension()
    {
        return null;
    }
}
