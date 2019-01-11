<?php

namespace Mapbender\CoreBundle\Template;

/**
 * Template Responsive
 *
 * @author Vadim Hermann
 * @deprecated
 */
class Responsive extends Fullscreen
{
    protected static $title   = "Responsive";
    protected static $regions = array('toolbar', 'content', 'infocontainer');
    protected static $css     = array(
        '@MapbenderCoreBundle/Resources/public/sass/template/responsive.scss'
    );
    protected static $js      = array(
        '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
        '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
        '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
        '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
        "/components/vis-ui.js/vis-ui.js-built.js",
        '@MapbenderCoreBundle/Resources/public/js/responsive.js'
    );

    public $twigTemplate = 'MapbenderCoreBundle:Template:responsive.html.twig';

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
