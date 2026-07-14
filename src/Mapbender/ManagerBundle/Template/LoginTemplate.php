<?php
namespace Mapbender\ManagerBundle\Template;

class LoginTemplate extends ManagerTemplate
{
    public function getAssets($type): array
    {
        return match ($type) {
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
                '@MapbenderManagerBundle/Resources/public/sass/manager/variables.scss',
                '@MapbenderManagerBundle/Resources/public/sass/manager/login.scss',
            ],
            'trans' => [],
            default => parent::getAssets($type),
        };
    }
}
