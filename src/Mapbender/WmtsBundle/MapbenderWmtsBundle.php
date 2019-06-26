<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\WmtsBundle\DependencyInjection\Compiler\RegisterWmtsExportLayerRendererPass;
use Mapbender\WmtsBundle\DependencyInjection\Compiler\RegisterWmtsSourceServicePass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * MapbenderWmtsBundle
 */
class MapbenderWmtsBundle extends MapbenderBundle
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

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
//            'Mapbender\WmtsBundle\Element\WmtsLoader'
        );
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryManagers()
    {
        return array(
            'wmts' => array(
                'id' => 'wmts',
                'label' => 'OGC WMTS / TMS',
                'manager' => 'mapbender_wmts_repository',
                'updateformAction' => "MapbenderWmtsBundle:Repository:updateform",
                'bundle' => "MapbenderWmtsBundle"
            )
        );
    }
}
