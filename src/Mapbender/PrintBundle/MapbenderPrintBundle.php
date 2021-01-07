<?php
namespace Mapbender\PrintBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\PrintBundle\DependencyInjection\Compiler\AddBasePrintPluginsPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * PrintBundle.
 *
 * @author Stefan Winkelmann
 */
class MapbenderPrintBundle extends MapbenderBundle
{
    public function build(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');

        $container->addCompilerPass(new AddBasePrintPluginsPass());
        parent::build($container);
    }

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\PrintBundle\Element\ImageExport',
            'Mapbender\PrintBundle\Element\PrintClient',
        );
    }

}

