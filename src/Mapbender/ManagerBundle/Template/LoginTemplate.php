<?php
namespace Mapbender\ManagerBundle\Template;

/**
 * Application backoffice login template
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 03.02.2015 by WhereGroup GmbH & Co. KG
 */
class LoginTemplate extends ManagerTemplate
{
    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderManagerBundle/Resources/public/sass/manager/login.scss'
                );
            default:
                return parent::getAssets($type);
        }
    }
}
