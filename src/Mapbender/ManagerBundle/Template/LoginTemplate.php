<?php
namespace Mapbender\ManagerBundle\Template;

class LoginTemplate extends ManagerTemplate
{
    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderManagerBundle/Resources/public/sass/manager/login.scss',
                );
            case 'trans':
                return array();
            default:
                return parent::getAssets($type);
        }
    }
}
