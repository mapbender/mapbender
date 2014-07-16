<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template Responsive
 *
 * @author Vadim Hermann
 */
class Responsive extends Template
{

    /**
     * @inheritdoc
     */
    public static function getRegionsProperties()
    {
        return array(
            'sidepane' => array(
                'tabs' => array(
                    'name' => 'tabs',
                    'icon' => 'iconTab',
                    'label' => 'mb.manager.template.region.tabs.label'),
                'accordion' => array(
                    'name' => 'accordion',
                    'icon' => 'iconAccordion',
                    'label' => 'mb.manager.template.region.accordion.label')
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Responsive';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $assets = array(
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/theme/mapbender3.scss',
                '@MapbenderCoreBundle/Resources/public/sass/template/responsive.scss'),
            'js' => array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@MapbenderCoreBundle/Resources/public/js/responsive.js'),
            'trans' => array()
        );
        return $assets;
    }

    /**
     * @inheritdoc
     */
    public function getAssets($type)
    {
        $assets = $this::listAssets();
        return $assets[$type];
    }

    /**
     * @inheritdoc
     */
    public static function getRegions()
    {
        return array('toolbar', 'content', 'infocontainer');
    }

    /**
     * @inheritdoc
     */
    public static function getElementWhitelist()
    {
        return array('toolbar' => array('Mapbender\CoreBundle\Element\Button',
                'Mapbender\CoreBundle\Element\AboutDialog'),
            'content' => array('Mapbender\CoreBundle\Element\ActivityIndicator',
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

    /**
     * @inheritdoc
     */
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        $region_props = $this->application->getEntity()->getNamedRegionProperties();
        $default_region_props = $this->getRegionsProperties();

        $templating = $this->container->get('templating');
        return $templating
                ->render('MapbenderCoreBundle:Template:responsive.html.twig', array(
                    'html' => $html,
                    'css' => $css,
                    'js' => $js,
                    'application' => $this->application,
                    'region_props' => $region_props,
                    'default_region_props' => $default_region_props));
    }

}
