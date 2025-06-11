<?php

namespace Mapbender\WmsBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MapbenderWmsBundle extends Bundle
{

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');
        $loader->load('commands.xml');
        $loader->load('elements.xml');
    }
}
