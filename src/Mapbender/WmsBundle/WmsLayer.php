<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\Layer;

/**
 * Base WMS class
 *
 * @author Christian Wygoda
 */
class WmsLayer extends Layer {
    public function getType() {
        return 'wms';
    }

    public function getAssets($type) {
        parent::getAssets($type);

        switch($type) {
        case 'js':
            return array('@MapbenderWmsBundle/Resources/public/mapbender.layer.wms.js');
        case 'css':
            return array();
        }
    }
}

