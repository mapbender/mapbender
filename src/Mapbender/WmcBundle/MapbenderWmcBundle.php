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

class MapbenderWmcBundle extends MapbenderBundle
{

    public function getElements()
    {
	return array(
	    // WATCHOUT: Available in the next versions
	    // Current version: 3.0.0.2
	    'Mapbender\WmcBundle\Element\WmcLoader',
	    'Mapbender\WmcBundle\Element\SuggestMap',
	);
    }

}

