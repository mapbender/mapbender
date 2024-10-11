<?php

namespace Mapbender\RoutingBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Mapbender routing bundle
 *
 * @author David Patzke
 * @author Andriy Oblivantsev
 */
class MapbenderRoutingBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container): void
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('elements.xml');
        parent::build($container);
    }
}
