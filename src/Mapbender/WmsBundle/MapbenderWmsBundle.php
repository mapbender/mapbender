<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\WmsBundle\DependencyInjection\Compiler\RegisterWmsSourceServicePass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * MapbenderWmsBundle
 */
class MapbenderWmsBundle extends MapbenderBundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');

        $container->addCompilerPass(new RegisterWmsSourceServicePass());
    }

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        $elements = array(
            'Mapbender\WmsBundle\Element\WmsLoader',
        );
        if ($this->container->getParameter('mapbender.preview.element.dimensionshandler')) {
            $elements[] = 'Mapbender\WmsBundle\Element\DimensionsHandler';
        }
        return $elements;
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryManagers()
    {
        return array(
            'wms' => array(
                'id' => 'wms',
                'label' => 'OGC WMS',
                'manager' => 'mapbender_wms_repository',
                'startAction' => "MapbenderWmsBundle:Repository:start",
                'updateformAction' => "MapbenderWmsBundle:Repository:updateform",
                'bundle' => "MapbenderWmsBundle"
            )
        );
    }
}
