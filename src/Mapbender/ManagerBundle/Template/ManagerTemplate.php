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
    public function getAssets($type)
    {
        switch ($type) {
            case 'js':
                return array(
                    '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
                    '/components/vis-ui.js/vis-ui.js-built.js',

                    '@MapbenderManagerBundle/Resources/public/js/SymfonyAjaxManager.js',

                    '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
                    '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js',
                    '@FOMCoreBundle/Resources/public/js/widgets/radiobuttonExtended.js',
                    '@FOMCoreBundle/Resources/public/js/components.js',
                    '@FOMCoreBundle/Resources/public/js/widgets/collection.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                );
            case 'css':
                return array(
                    '@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
                );
            case 'trans':
                return array(
                    '@MapbenderManagerBundle/Resources/views/translations.json.twig'
                );
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    /**
     * @inheritdoc
     */
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        return "";
    }
}
