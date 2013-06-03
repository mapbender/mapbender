<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mapbender\WmcBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

class MapbenderWmcBundle extends MapbenderBundle {
    public function getElements() {
        return array(
//            'Mapbender\WmcBundle\Element\WmcEditor',
//            'Mapbender\WmcBundle\Element\WmcHandler'
            );
    }
}

