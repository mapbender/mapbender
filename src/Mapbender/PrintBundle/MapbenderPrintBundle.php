<?php
namespace Mapbender\PrintBundle;

use Mapbender\PrintBundle\DependencyInjection\Compiler\AddBasePrintPluginsPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * PrintBundle.
 *
 * @author Stefan Winkelmann
 */
class MapbenderPrintBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');
        $loader->load('elements.xml');
        $loader->load('commands.xml');

        $container->addCompilerPass(new AddBasePrintPluginsPass());
        parent::build($container);
    }
}

