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
        $container->setParameter('mapbender.uploads_dir', $config['uploads_dir']);
        $container->setParameter('mapbender.screenshot_path', $screenshot_path);

        $container->setParameter("mapbender.selfregister", $config["selfregister"]);
        $container->setParameter("mapbender.max_registration_time", intval($config["max_registration_time"]));
        $container->setParameter("mapbender.max_reset_time", intval($config["max_reset_time"]));

        $container->setParameter("mapbender.static_assets", $config["static_assets"]);
        $container->setParameter("mapbender.static_assets_cache_path", $config["static_assets_cache_path"]);

        $now = new \DateTime('now');
        $container->setParameter("mapbender.cache_creation", $now->format('c'));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function getAlias() {
        return 'mapbender_core';
    }
}
