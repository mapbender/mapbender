<?php
namespace Mapbender\KmlBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * KmlBundle.
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class MapbenderKmlBundle extends MapbenderBundle {

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\KmlBundle\Element\KmlExport'
            );
    }

}
