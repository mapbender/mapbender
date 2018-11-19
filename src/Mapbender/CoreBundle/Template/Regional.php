<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Class Regional
 *
 * @package   Mapbender\CoreBundle\Template
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class Regional extends Template
{

    /**
     * @inheritdoc
     */
    public static function getRegionsProperties()
    {
        return array('sidepane' => array('tabs'      => array('name'  => 'tabs',
                                                              'label' => 'mb.manager.template.region.tabs.label'),
                                         'accordion' => array('name'  => 'accordion',
                                                              'label' => 'mb.manager.template.region.accordion.label')));
    }

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return preg_replace('|.*\\\|', '', __CLASS__);
    }

    /**
     * @inheritdoc
     */
    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderCoreBundle/Resources/public/sass/template/responsive.scss',
                );
            case 'js':
                return array(
                    '/components/underscore/underscore-min.js',
                    '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                    '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                    '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
                    '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js',
                    "/components/datatables/media/js/jquery.dataTables.min.js",
                    '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
                    "/components/vis-ui.js/vis-ui.js-built.js",
                    '@MapbenderCoreBundle/Resources/public/js/responsive.js',
                );
            case 'trans':
            default:
                return parent::getAssets($type);
        }
    }

    /**
     * @inheritdoc
     */
    public static function getRegions()
    {
        return array('top', 'left', 'center', 'right', 'bottom');
    }

    /**
     * @inheritdoc
     */
    public static function getElementWhitelist()
    {
        return array('toolbar'       => array('Mapbender\CoreBundle\Element\Button',
                                              'Mapbender\CoreBundle\Element\AboutDialog'),
                     'content'       => array('Mapbender\CoreBundle\Element\ActivityIndicator',
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
                     'infocontainer' => array('Mapbender\CoreBundle\Element\AboutDialog',
                                              'Mapbender\CoreBundle\Element\BaseSourceSwitcher',
                                              'Mapbender\CoreBundle\Element\CoordinatesDisplay',
                                              'Mapbender\CoreBundle\Element\Copyright',
                                              'Mapbender\CoreBundle\Element\ScaleBar',
                                              'Mapbender\CoreBundle\Element\ScaleSelector',
                                              'Mapbender\CoreBundle\Element\SrsSelector'));
    }

    public function getTwigTemplate()
    {
        return 'MapbenderCoreBundle:Template:regional.html.twig';
    }
}
