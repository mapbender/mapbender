<?php
/**
 * Application manager template
 *
 * TODO:
 *  - JavaScript and translations are defined, but never used
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 03.02.2015 by WhereGroup GmbH & Co. KG
 */
namespace Mapbender\ManagerBundle\Template;

use Mapbender\CoreBundle\Component\Template;

class ManagerTemplate extends Template
{
    protected static $translations = array();
    protected static $cssList      = array('@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
                                    '@MapbenderManagerBundle/Resources/public/sass/manager/manager.scss',
                                    "@FOMUserBundle/Resources/public/sass/user/user_control.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/form.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/yaml.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/button.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/activityindicator.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/copyright.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/featureinfo.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/gpsposition.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/layertree.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/legend.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/map.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/overview.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/printclient.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/scalebar.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/search_router.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/zoombar.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/basesourceswitcher.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/element/simplesearch.scss",
                                    "@MapbenderManagerBundle/Resources/public/sass/manager/services.scss",

    );

    protected static $jscripts = array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                                       '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
                                       '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js',
                                       '@FOMCoreBundle/Resources/public/js/widgets/radiobuttonExtended.js',
                                       '@FOMCoreBundle/Resources/public/js/components.js',
                                       '@FOMCoreBundle/Resources/public/js/widgets/collection.js',
                                       '@MapbenderCoreBundle/Resources/public/mapbender.trans.js');

    static public function listAssets()
    {
        return array('css'   => self::$cssList,
                     'js'    => self::$jscripts,
                     'trans' => self::$translations);
    }

    /**
     * @inheritdoc
     */
    public function getLateAssets($type)
    {
        $assets = array('css'   => array(),
                        'js'    => array(),
                        'trans' => array());
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
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        return "";
    }
} 