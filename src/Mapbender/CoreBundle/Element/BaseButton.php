<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Component\Element;

abstract class BaseButton extends Element
{
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:button.html.twig';
    }

    public static function getDefaultConfiguration()
    {
        return array(
            /** @see \Mapbender\CoreBundle\Element\Type\BaseButtonAdminType::buildForm */
            'label' => true,
            'tooltip' => null,
            'icon' => null,
        );
    }
}
