<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\WmtsBundle\DependencyInjection\Compiler\RegisterWmtsExportLayerRendererPass;
use Mapbender\WmtsBundle\DependencyInjection\Compiler\RegisterWmtsSourceServicePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * MapbenderWmtsBundle
 */
class MapbenderWmtsBundle extends MapbenderBundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
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
                'startAction' => "MapbenderWmtsBundle:Repository:start",
                'updateformAction' => "MapbenderWmtsBundle:Repository:updateform",
                'bundle' => "MapbenderWmtsBundle"
            )
        );
    }
}
