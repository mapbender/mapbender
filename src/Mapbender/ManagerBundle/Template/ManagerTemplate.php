<?php

namespace Mapbender\ManagerBundle\Template;

use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateAssetDependencyInterface;

/**
 * Not an application template.
 *
 * The actual twigs used to render the backend pages vary signficantly by controller action.
 *
 * The only commonality of the manager application is that it uses the same mechanism as proper
 * application templates to declare and generate its required assets. See (currently in FOM):
 * https://github.com/mapbender/fom/blob/v3.0.6.2/src/FOM/ManagerBundle/Resources/views/manager.html.twig#L8
 */
class ManagerTemplate implements IApplicationTemplateAssetDependencyInterface
{
    /**
     * {@inheritdoc}
     */
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
                throw new \InvalidArgumentException("Unsupported late asset type " . print_r($type, true));
        }
    }

    /**
     * ManagerTemplate does not use late assets. Required only for compatibility with application assets action.
     *
     * @param string $type one of 'css', 'js' or 'trans'
     * @return string[]
     */
    public function getLateAssets($type)
    {
        switch ($type) {
            case 'js':
            case 'css':
            case 'trans':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported late asset type " . print_r($type, true));
        }
    }
}
