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
            'Mapbender\WmsBundle\Element\DimensionsHandler',
        );
        return $elements;
    }
}
