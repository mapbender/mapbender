<?php

namespace Mapbender\ManagerBundle\Template;

use Mapbender\Component\Application\TemplateAssetDependencyInterface;

class ManagerTemplate implements TemplateAssetDependencyInterface
{
    public function getAssets($type): array
    {
        return match ($type) {
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
                '@MapbenderManagerBundle/Resources/public/sass/manager/variables.scss',
                '@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
            ],
            'js' => [
                '@MapbenderManagerBundle/Resources/public/js/bootstrap-modal.js',
                '@MapbenderCoreBundle/Resources/public/widgets/content-toggle.js',
                '@MapbenderManagerBundle/Resources/public/components.js',
                '@MapbenderManagerBundle/Resources/public/form/collection.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                '@MapbenderManagerBundle/Resources/public/js/confirm-delete.js',
                '/components/bootstrap/js/bootstrap.bundle.js',
            ],
            'trans' => [
                'mb.actions.*',
                'mb.manager.components.popup.*',
                'mb.manager.managerbundle.add_user_group',
                'mb.manager.admin.application.upload.label',
                'mb.core.entity.app.screenshotfile.*',
                'mb.application.save.failure.general',
                'mb.manager.confirm_form_discard',
                'mb.manager.admin.style.*',
                'mb.ogcapifeatures.admin.filter.*',
            ],
            default => throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true)),
        };
    }

    public function getLateAssets($type)
    {
        return match ($type) {
            'css', 'js', 'trans' => [],
            default => throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true)),
        };
    }
}
