<?php

/*
 * This file is part of the Mapbender 3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
