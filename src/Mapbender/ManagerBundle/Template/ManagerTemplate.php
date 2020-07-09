<?php

namespace Mapbender\ManagerBundle\Template;

use Mapbender\Component\Application\TemplateAssetDependencyInterface;

class ManagerTemplate implements TemplateAssetDependencyInterface
{
    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
                );
            case 'js':
                return array(
                    '/components/jquerydialogextendjs/jquerydialogextendjs-built.js',
                    '/components/vis-ui.js/vis-ui.js-built.js',

                    '@MapbenderCoreBundle/Resources/public/widgets/dropdown.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js',
                    '@MapbenderManagerBundle/Resources/public/components.js',
                    '@MapbenderManagerBundle/Resources/public/form/collection.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                    '@MapbenderManagerBundle/Resources/public/js/confirm-delete.js',
                );
            case 'trans':
                return array(
                    'mb.actions.*',
                    'fom.core.components.popup.add_user_group.title',
                    'fom.core.components.popup.delete_user_group.title',
                    'fom.core.components.popup.delete_user_group.content',
                    'mb.manager.components.popup.*',
                    'mb.manager.upload.label_delete',
                    'mb.manager.admin.application.upload.label',
                    'mb.core.entity.app.screenshotfile.*',
                    'mb.application.save.failure.general',
                    'mb.manager.confirm_form_discard',
                );
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    public function getLateAssets($type)
    {
        switch ($type) {
            case 'css':
            case 'js':
            case 'trans':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }
}
