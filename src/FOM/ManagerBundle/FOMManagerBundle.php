<?php

namespace FOM\ManagerBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * FoMManagerBundle - provides Manager interface infrastructure for other bundles.
 *
 * @author Christian Wygoda
 */
class FOMManagerBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.xml');
    }
}
