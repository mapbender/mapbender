<?php
namespace Mapbender\RoutingBundle;

//use Mapbender\PrintBundle\DependencyInjection\Compiler\AddBasePrintPluginsPass;
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
    /*public function getElements()
    {
        return array(
            'Mapbender\RoutingBundle\Element\RoutingElement'
        );
    }*/

    public function build(ContainerBuilder $container): void
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
//        $loader->load('services.xml');
//        $loader->load('elements.xml');
//        $loader->load('commands.xml');

//        $container->addCompilerPass(new AddBasePrintPluginsPass());
        parent::build($container);
    }
}
