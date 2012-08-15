<?php

namespace Mapbender\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MapbenderCoreExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $screenshot_path = $container->getParameter('kernel.root_dir')
            . '/../web/' . $config['screenshot_path'];
        $container->setParameter('mapbender.proxy', $config['proxy']);
        $container->setParameter('mapbender.screenshot_path', $screenshot_path);

        $container->setParameter("mapbender.selfregister", $config["selfregister"]);
        $container->setParameter("mapbender.max_registration_time", intval($config["max_registration_time"]));
        $container->setParameter("mapbender.max_reset_time", intval($config["max_reset_time"]));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function getAlias() {
        return 'mapbender_core';
    }
}

