<?php

namespace Mapbender\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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

        $container->setParameter("mapbender.static_assets", !$config["sass_assets"]);
        $container->setParameter("mapbender.sass_assets", $config["sass_assets"]);
        $container->setParameter("mapbender.static_assets_cache_path", $config["static_assets_cache_path"]);

        $now = new \DateTime('now');
        $container->setParameter("mapbender.cache_creation", $now->format('c'));

        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $xmlLoader->load('services.xml');
        
        $ymlLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $ymlLoader->load('mapbender.yml');
        $ymlLoader->load('components.yml');
        $ymlLoader->load('commands.yml');
        $ymlLoader->load('migrations.yml');
    }

    public function getAlias() {
        return 'mapbender_core';
    }
}
