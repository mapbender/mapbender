<?php


namespace Mapbender\CoreBundle\Element;


class LinkButton extends BaseButton
{
    public static function getClassTitle()
    {
        return 'mb.core.linkbutton.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.linkbutton.class.description';
    }

    public function getWidgetName()
    {
        return false;
    }

    public static function getDefaultConfiguration()
    {
        return array_replace(parent::getDefaultConfiguration(), array(
            'click' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LinkButtonAdminType';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:link_button.html.twig';
    }
}
