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
    public static function getTitle()
    {
        throw new \RuntimeException("This is never called");
    }

    public static function getRegions()
    {
        throw new \RuntimeException("This is never called");
    }

    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
                );
            case 'js':
                return array(
                    '/components/underscore/underscore-min.js',
                    '/bundles/mapbendercore/regional/vendor/notify.0.3.2.min.js',
                    '/components/datatables/media/js/jquery.dataTables.min.js',
                    '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
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
            case 'trans':
                return array(
                    '@MapbenderManagerBundle/Resources/views/translations.json.twig',
                );
            default:
                return parent::getAssets($type);
        }
    }

    public function getTwigTemplate()
    {
        // NOTE: Old versions had an overriden render method that returns an empty string.
        //       Url path /application/manager itself never functioned
        //       Actual rendering of the backend is in WelcomeController::listAction, which uses the template
        //       MapbenderCoreBundle:Welcome:list.html.twig
        //       Url paths /application/manager/assets/js et al
        throw new \RuntimeException("ManagerTemplate does not support standard rendering, only provides asset dependencies");
    }
}
