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
    protected static $title             = "Fullscreen";
    protected static $regions           = array('toolbar', 'sidepane', 'content', 'footer');
    protected static $regionsProperties = array(
        'sidepane' => array(
            'tabs'      => array(
                'name'  => 'tabs',
                'label' => 'mb.manager.template.region.tabs.label'),
            'accordion' => array(
                'name'  => 'accordion',
                'label' => 'mb.manager.template.region.accordion.label')
        )
    );
    protected static $css               = array(
        '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen.scss'
    );
    protected static $js                = array(
        '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
        '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
        '@MapbenderCoreBundle/Resources/public/mapbender.container.info.js',
        '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
    );

    public $twigTemplate = 'MapbenderCoreBundle:Template:fullscreen.html.twig';
}
