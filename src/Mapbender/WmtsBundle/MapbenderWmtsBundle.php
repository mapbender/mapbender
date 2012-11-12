<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

class MapbenderWmtsBundle extends MapbenderBundle {
    public function getRepositoryManagers()
    {
        return array(
            'wms' => array(
                'id'    => 'wmts',
                'label' => 'OGC WMTS',
                'manager' => 'mapbender_wmts_repository',
                'startAction' => "MapbenderWmtsBundle:Repository:start"
            )
        );
    }
}