<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template Fullscreen
 *
 * @author Christian Wygoda
 */
class Fullscreen extends Template
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
                    'label' => 'mb.manager.template.region.tabs.label'),
                'accordion' => array(
                    'name' => 'accordion',
                    'label' => 'mb.manager.template.region.accordion.label')
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Fullscreen';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $assets = array(
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/theme/mapbender3.scss',
                '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen.scss'),
            'js' => array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
                '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js'
            ),
            'trans' => array()
        );
        return $assets;
    }

    /**
     * @inheritdoc
     */
    public function getLateAssets($type)
    {
        $assets = array(
            'css' => array(),
            'js' => array(),
            'trans' => array()
        );
        return $assets[$type];
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
        return array('toolbar', 'sidepane', 'content', 'footer');
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
                ->render('MapbenderCoreBundle:Template:fullscreen.html.twig', array(
                    'html' => $html,
                    'css' => $css,
                    'js' => $js,
                    'application' => $this->application,
                    'region_props' => $region_props,
                    'default_region_props' => $default_region_props));
    }

}
