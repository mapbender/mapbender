<?php

namespace Mapbender\MonitoringBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MapbenderMonitoringExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container) {

        $loader = new XmlFileLoader($container,
            new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function getAlias() {
        return 'mapbender_monitoring';
    }
}

