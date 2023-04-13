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
                    '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
                    '@MapbenderManagerBundle/Resources/public/sass/manager/variables.scss',
                    '@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
                );
            case 'js':
                return array(
                    '@MapbenderManagerBundle/Resources/public/js/bootstrap-modal.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/content-toggle.js',
                    '@MapbenderManagerBundle/Resources/public/components.js',
                    '@MapbenderManagerBundle/Resources/public/form/collection.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                    '@MapbenderManagerBundle/Resources/public/js/confirm-delete.js',
                    '/components/bootstrap/js/bootstrap.js',
                );
            case 'trans':
                return array(
                    'mb.actions.*',
                    'mb.manager.components.popup.*',
                    'mb.manager.managerbundle.add_user_group',
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
