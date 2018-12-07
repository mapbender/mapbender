<?php
namespace Mapbender\PrintBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\PrintBundle\DependencyInjection\Compiler\AddBasePrintPluginsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * PrintBundle.
 *
 * @author Stefan Winkelmann
 */
class MapbenderPrintBundle extends MapbenderBundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddBasePrintPluginsPass());
        parent::build($container);
    }

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\PrintBundle\Element\ImageExport'
            );
    }

}

