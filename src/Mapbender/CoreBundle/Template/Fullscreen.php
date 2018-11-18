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

    public $twigTemplate = 'MapbenderCoreBundle:Template:fullscreen.html.twig';

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'css'   => array('@MapbenderCoreBundle/Resources/public/sass/template/fullscreen.scss'),
            'js'    => array(
                '/components/underscore/underscore-min.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.container.info.js',
                '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js',
                "/components/datatables/media/js/jquery.dataTables.min.js",
                '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
                "/components/vis-ui.js/vis-ui.js-built.js"

            ),
            'trans' => array()
        );
    }
}
