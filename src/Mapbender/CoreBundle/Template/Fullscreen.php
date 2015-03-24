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
            'css' => array(
//                '@MapbenderCoreBundle/Resources/public/sass/theme/mapbender3.scss',
                '@MapbenderCoreBundle/Resources/public/fonts/opensans_regular_macroman/stylesheet.scss',
                '@MapbenderCoreBundle/Resources/public/sass/template/fullscreen.scss'),
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/regional/EventDispatcher.js',
                '@MapbenderCoreBundle/Resources/public/libs/StringHelper.js',
                '@MapbenderCoreBundle/Resources/public/regional/vendor/jquery/fn.formData.js',

                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js',
                '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js',
                '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js',


//                                    'vendor/underscore.js',
//                                    'vendor/json2.js',
//                                    'vendor/backbone.js',
//                                    'vendor/jquery.form.min.js',

                /**
                 * @copyright 2008-2014 SpryMedia Ltd - datatables.net/license
                 * Released under the MIT license: http://jsbin.mit-license.org
                 */
                '@MapbenderCoreBundle/Resources/public/regional/vendor/jquery/jquery.dataTables.1.10.3.min.js',

                /**
                 * @copyright (c) 2014 by anonymous (http://jsbin.com/ehagoy/154/edit)
                 * Released under the MIT license: http://jsbin.mit-license.org
                 */
                '@MapbenderCoreBundle/Resources/public/regional/vendor/jquery/jquery.dialogextend.2.0.3.js',

                '@MapbenderCoreBundle/Resources/public/mapbender.element.resultTable.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.element.tabNavigator.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.element.popupDialog.js'


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

        if(!is_array( $region_props)){
            $region_props = array();
        }

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
