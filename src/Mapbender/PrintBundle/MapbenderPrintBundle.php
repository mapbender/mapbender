<?php

/*
 * This file is part of the Mapbender 3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mapbender\PrintBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

/**
 * PrintBundle.
 *
 * @author Stefan Winkelmann
 */
class MapbenderPrintBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\PrintBundle\Element\ImageExport'
            );
    }

}

