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
 * @author Christian Wygoda
 */
class MapbenderCoreBundle extends MapbenderBundle
{
    public function getTemplates()
    {
        return array('Mapbender\CoreBundle\Template\Fullscreen');
    }

    public function getElements()
    {
        return array(
            'Mapbender\CoreBundle\Element\AboutDialog',
            'Mapbender\CoreBundle\Element\ActivityIndicator',
            'Mapbender\CoreBundle\Element\Button',
            'Mapbender\CoreBundle\Element\CoordinatesDisplay',
            'Mapbender\CoreBundle\Element\Copyright',
            'Mapbender\CoreBundle\Element\FeatureInfo',
            'Mapbender\CoreBundle\Element\Legend',
            'Mapbender\CoreBundle\Element\Map',
            'Mapbender\CoreBundle\Element\Ruler',
            'Mapbender\CoreBundle\Element\ScaleSelector',
//            'Mapbender\CoreBundle\Element\SearchRouter',
            'Mapbender\CoreBundle\Element\SrsSelector',
            'Mapbender\CoreBundle\Element\Toc',
            'Mapbender\CoreBundle\Element\ZoomBar');
    }
}

