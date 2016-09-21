<?php

namespace Mapbender\ManagerBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Application manager template
 *
 * @copyright 03.02.2015 by WhereGroup GmbH & Co. KG
 */
class ManagerTemplate extends Template
{
    protected static $css = array(
        '@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
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
        "@MapbenderManagerBundle/Resources/public/sass/element/simple_search.scss",
        "@MapbenderManagerBundle/Resources/public/sass/manager/services.scss",
    );

    protected static $js = array(
        '/components/underscore/underscore-min.js',
        '/bundles/mapbendercore/regional/vendor/notify.0.3.2.min.js',
        '/components/datatables/media/js/jquery.dataTables.min.js',
        '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
        '/components/vis-ui.js/vis-ui.js-built.js',
        '/bundles/fosjsrouting/js/router.js',

        '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
        '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
        '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js',
        '@FOMCoreBundle/Resources/public/js/widgets/radiobuttonExtended.js',
        '@FOMCoreBundle/Resources/public/js/components.js',
        '@FOMCoreBundle/Resources/public/js/widgets/collection.js',
        '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
    );

    protected static $translations = array(
        '@MapbenderManagerBundle/Resources/views/translations.json.twig'
    );

    /**
     * @inheritdoc
     */
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        return "";
    }
} 