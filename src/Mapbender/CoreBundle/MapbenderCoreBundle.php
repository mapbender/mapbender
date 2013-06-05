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
    /**
     * @inheritdoc
     */
    public function getTemplates()
    {
        return array(
            'Mapbender\CoreBundle\Template\Fullscreen',
            'Mapbender\CoreBundle\Template\Base',
            'Mapbender\CoreBundle\Template\Base2',
            'Mapbender\CoreBundle\Template\SidebarLeft'
            );
    }

    /**
     * @inheritdoc
     */
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
            'Mapbender\CoreBundle\Element\Overview',
            'Mapbender\CoreBundle\Element\Ruler',
            'Mapbender\CoreBundle\Element\ScaleBar',
            'Mapbender\CoreBundle\Element\ScaleSelector',
//            'Mapbender\CoreBundle\Element\SearchRouter',
            'Mapbender\CoreBundle\Element\SrsSelector',
            'Mapbender\CoreBundle\Element\Toc',
            'Mapbender\CoreBundle\Element\Layertree',
            'Mapbender\CoreBundle\Element\ZoomBar',
            'Mapbender\CoreBundle\Element\PrintClient',
            'Mapbender\CoreBundle\Element\GpsPosition',
            'Mapbender\CoreBundle\Element\ScaleDisplay'
            );
    }

    /**
     * @inheritdoc
     */
    public function getACLClasses()
    {
        return array(
            'Mapbender\CoreBundle\Entity\Application' => 'Application');
    }
}

