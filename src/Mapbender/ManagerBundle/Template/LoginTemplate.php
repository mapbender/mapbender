<?php
/**
 * Application backoffice login template
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 03.02.2015 by WhereGroup GmbH & Co. KG
 */
namespace Mapbender\ManagerBundle\Template;

class LoginTemplate extends ManagerTemplate
{
    static public function listAssets()
    {
        return array('css'   => array('@FOMUserBundle/Resources/public/sass/user/login.scss',
            '@FOMUserBundle/Resources/public/sass/user/user_control.scss'),
                     'js'    => self::$jscripts,
                     'trans' => self::$translations);
    }
}