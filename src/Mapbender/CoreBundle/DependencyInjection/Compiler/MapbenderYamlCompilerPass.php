<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\ConfigCache;


class MapbenderYamlCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->loadYamlApplications($container);
    }

    private function loadYamlApplications(ContainerBuilder $container)
    {
        $configDir = $container->getParameter('kernel.root_dir') . '/config';
        $finder = new Finder();

        $finder
            ->in($configDir)
            ->files()->name('mapbender.yml');

        foreach($finder as $file) {
            $container->addResource(new FileResource($file->getRealPath()));
            $this->loadYaml($container, $file);
        }

        if(is_dir($configDir . '/applications')) {
            $finder = new Finder();
            $finder
                ->in($configDir . '/applications')
                ->files()->name('*.yml');
            foreach($finder as $file) {
                $container->addResource(new FileResource($file->getRealPath()));
                $this->loadYaml($container, $file);
            }
        }

        $container->getParameterBag()->resolve();
    }

    private function loadYaml(ContainerBuilder $container, $file)
    {
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
}
