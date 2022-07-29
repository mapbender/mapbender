<?php

namespace Mapbender\MobileBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MapbenderMobileBundle
 *
 * @package Mapbender\MobileBundle
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
class MapbenderMobileBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('templates.xml');
        $container->addResource(new FileResource($configLocator->locate('templates.xml')));
    }

    public function getContainerExtension()
    {
        return null;
    }
}
