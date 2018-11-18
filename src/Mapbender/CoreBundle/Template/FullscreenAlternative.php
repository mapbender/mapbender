<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template FullscreenAlternative
 *
 * @author Christian Wygoda
 */
class FullscreenAlternative extends Template
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
        return 'Fullscreen alternative';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $assets = array(
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/template/fullscreen_alternative.scss'),
            'js' => array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js'),
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
        return array('toolbar', 'sidepane', 'content', 'footer');
    }

    public function getTwigTemplate()
    {
        return 'MapbenderCoreBundle:Template:fullscreen_alternative.html.twig';
    }

}
