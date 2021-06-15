<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\Component\Element\ButtonLike;
use Mapbender\CoreBundle\Component\Element;

abstract class BaseButton extends Element
{
    public static function getFormTemplate()
    {
        return ButtonLike::getFormTemplate();
    }

    public static function getDefaultConfiguration()
    {
        return ButtonLike::getDefaultConfiguration();
    }

    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/button.scss',
            ),
        );
    }
}
