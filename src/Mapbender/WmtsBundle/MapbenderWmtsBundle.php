<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

class MapbenderWmtsBundle extends MapbenderBundle {
    
    public function getRepositoryManagers()
    {
        // VH: 20130313 - under development
        // return array(
        //     'wmts' => array(
        //         'id'    => 'wmts',
        //         'label' => 'OGC WMTS',
        //         'manager' => 'mapbender_wmts_repository',
        //         'startAction' => "MapbenderWmtsBundle:Repository:start",
        //         'bundle' => "MapbenderWmtsBundle"
        //     )
        // );

        return array();
    }
}
