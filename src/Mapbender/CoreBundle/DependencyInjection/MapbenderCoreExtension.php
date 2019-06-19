<?php

namespace Mapbender\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;


class MapbenderCoreExtension extends Extension
{
    const CONFIG_PATH = '/../Resources/config';

    /**
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * MapbenderCoreExtension constructor.
     */
    public function __construct()
    {
        $this->fileLocator = new FileLocator(__DIR__ . self::CONFIG_PATH);
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        if ($config['uploads_dir'] !== false) {
            $container->setParameter('mapbender.uploads_dir', $config['uploads_dir']);
        }

        $now = new \DateTime('now');
        $container->setParameter("mapbender.cache_creation", $now->format('c'));

        $this
            ->loadXmlConfigs($container)
            ->loadYmlConfigs($container);
    }

    public function getAlias() {
        return 'mapbender_core';
    }

    /**
     * @param $container
     * @return $this
     */
    protected function loadXmlConfigs($container)
    {
        $loader = new XmlFileLoader($container, $this->fileLocator);
        $configFiles = $this->getXmlConfigs();

        $this->loadConfigs($loader, $configFiles);

        return $this;
    }

    /**
     * @param $container
     * @return $this
     */
    protected function loadYmlConfigs($container)
    {
        $loader = new YamlFileLoader($container, $this->fileLocator);
        $configs = $this->getYmlConfigs();

        $this->loadConfigs($loader, $configs);

        return $this;
    }

    /**
     * @param $loader
     * @param $configs
     */
    protected function loadConfigs($loader, $configs)
    {
        foreach ($configs as $config) {
            $loader->load($config);
        }
    }

    /**
     * @return array
     */
    protected function getYmlConfigs()
    {
        return [
            'mapbender.yml',
            'constraints.yml',
            'formTypes.yml',
            'components.yml',
            'commands.yml',
            'migrations.yml',
        ];
    }

    /**
     * @return array
     */
    protected function getXmlConfigs()
    {
        return [
            'services.xml',
        ];
    }
}
