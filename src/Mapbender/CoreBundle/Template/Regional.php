<?php

namespace Mapbender\CoreBundle\Template;

/**
 * Class Regional
 *
 * @package   Mapbender\CoreBundle\Template
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class Regional extends Fullscreen
{
    protected static $title   = "Regional";
    protected static $regions = array('top', 'left', 'center', 'right', 'bottom');

    public $twigTemplate = 'MapbenderCoreBundle:Template:regional.html.twig';

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'css'   => array('@MapbenderCoreBundle/Resources/public/sass/template/responsive.scss'),
            'js'    => array(
                '/components/underscore/underscore-min.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
                '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js',
                "/components/datatables/media/js/jquery.dataTables.min.js",
                '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
                "/components/vis-ui.js/vis-ui.js-built.js",
                '@MapbenderCoreBundle/Resources/public/js/responsive.js'
            ),
            'trans' => array());
    }

    /**
     * @inheritdoc
     */
    public static function getElementWhitelist()
    {
        return array(
            'toolbar'       => array(
                'Mapbender\CoreBundle\Element\Button',
                'Mapbender\CoreBundle\Element\AboutDialog'),
            'content'       => array(
                'Mapbender\CoreBundle\Element\ActivityIndicator',
                'Mapbender\CoreBundle\Element\CoordinatesDisplay',
                'Mapbender\CoreBundle\Element\Copyright',
                'Mapbender\CoreBundle\Element\Map',
                'Mapbender\CoreBundle\Element\Overview',
                'Mapbender\CoreBundle\Element\POI',
                'Mapbender\CoreBundle\Element\PrintClient',
                'Mapbender\CoreBundle\Element\Ruler',
                'Mapbender\CoreBundle\Element\ScaleBar',
                'Mapbender\CoreBundle\Element\ScaleDisplay',
                'Mapbender\CoreBundle\Element\ScaleSelector',
                'Mapbender\CoreBundle\Element\SearchRouter',
                'Mapbender\CoreBundle\Element\SimpleSearch',
                'Mapbender\CoreBundle\Element\Sketch',
                'Mapbender\CoreBundle\Element\SrsSelector',
                'Mapbender\CoreBundle\Element\ZoomBar'),
            'infocontainer' => array(
                'Mapbender\CoreBundle\Element\AboutDialog',
                'Mapbender\CoreBundle\Element\BaseSourceSwitcher',
                'Mapbender\CoreBundle\Element\CoordinatesDisplay',
                'Mapbender\CoreBundle\Element\Copyright',
                'Mapbender\CoreBundle\Element\ScaleBar',
                'Mapbender\CoreBundle\Element\ScaleSelector',
                'Mapbender\CoreBundle\Element\SrsSelector')
        );
    }
}
