<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * MapbenderWmtsBundle
 */
class MapbenderWmtsBundle extends MapbenderBundle
{

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
