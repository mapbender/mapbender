<?php

namespace Mapbender\WmtsBundle;

use Mapbender\WmtsBundle\DependencyInjection\Compiler\RegisterWmtsExportLayerRendererPass;
use Mapbender\WmtsBundle\DependencyInjection\Compiler\RegisterWmtsSourceServicePass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MapbenderWmtsBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');

        $container->addCompilerPass(new RegisterWmtsSourceServicePass());
        $container->addCompilerPass(new RegisterWmtsExportLayerRendererPass());
    }
}
