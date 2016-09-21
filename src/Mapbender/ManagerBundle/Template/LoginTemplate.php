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
    protected static $translations = array();

    protected static $css = array(
        '@MapbenderManagerBundle/Resources/public/sass/manager/applications.scss',
        '@MapbenderManagerBundle/Resources/public/sass/manager/manager.scss',
        '@FOMUserBundle/Resources/public/sass/user/login.scss',
        '@FOMUserBundle/Resources/public/sass/user/user_control.scss'
    );
}