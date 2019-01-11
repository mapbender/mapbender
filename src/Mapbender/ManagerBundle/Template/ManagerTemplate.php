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
    );

    protected static $js = array(
        '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
        '/components/vis-ui.js/vis-ui.js-built.js',
        '/bundles/fosjsrouting/js/router.js',
        '@MapbenderManagerBundle/Resources/public/js/SymfonyAjaxManager.js',

        '@MapbenderCoreBundle/Resources/public/widgets/mapbender.popup.js',
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