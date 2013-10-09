<?php

namespace Mapbender\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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

        $now = new \DateTime('now');
        $container->setParameter("mapbender.cache_creation", $now->format('c'));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->loadYamlApplications($container);
    }

    private function loadYamlApplications(ContainerBuilder $container)
    {
        $configDir = $container->getParameter('kernel.root_dir') . '/config';
        $finder = new Finder();
        $finder2 = new Finder();

        $finder
            ->in($configDir)
            ->files()->name('mapbender.yml');

        if(is_dir($configDir . '/applications')) {
            $finder2
                ->in($configDir . '/applications')
                ->files()->name('*.yml');

            $finder->append($finder2);
        }

        foreach($finder as $file) {
            $yml = YAML::parse($file->getRealPath());
            if(array_key_exists('parameters', $yml) && is_array($yml['parameters'])) {
                foreach($yml['parameters'] as $key => $data) {
                    if($container->hasParameter($key)) {
                        $ante = $container->getParameter($key);
                        $container->setParameter(
                            $key,
                            array_merge_recursive($ante, $data));
                    } else {
                        $container->setParameter($key, $data);
                    }
                }
            }
        }
        $container->getParameterBag()->resolve();
    }

    public function getAlias() {
        return 'mapbender_core';
    }
}

