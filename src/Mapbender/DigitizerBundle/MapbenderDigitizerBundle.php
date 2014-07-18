<?php

/*
 * This file is part of the Mapbender 3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mapbender\DigitizerBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * DigitizerBundle.
 *
 * @author Stefan Winkelmann
 */
class MapbenderDigitizerBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\DigitizerBundle\Element\DigitizerToolbar'
            );
    }

}

