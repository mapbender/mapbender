<?php

/*
 * This file is part of the Mapbender 3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mapbender\CoreBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * CoreBundle.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
class MapbenderCoreBundle extends MapbenderBundle {
    public function getTemplates() {
        return array('Mapbender\CoreBundle\Template\Fullscreen');
    }
}

