<?php


namespace Mapbender\CoreBundle\Element;


class ShareUrl extends BaseButton
{
    // Disable being targetted by a Button
    public static $ext_api = false;

    public static function getClassTitle()
    {
        // @todo: translate
        return 'Share url';
    }

    public static function getClassDescription()
    {
        // @todo: translate
        return 'Share current map view via url';
    }

    public function getWidgetName()
    {
        return 'mapbender.mbShareUrl';
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ShareUrlAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js',
                '@MapbenderCoreBundle/Resources/public/element/mbShareUrl.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/button.scss',
                '@MapbenderCoreBundle/Resources/public/element/mbShareUrl.scss',
            ),
        );
    }

    public static function getDefaultConfiguration()
    {
        $defaults = parent::getDefaultConfiguration();
        // icon is hard-coded (see twig template)
        unset($defaults['icon']);
        return $defaults;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderCoreBundle:Element:ShareUrl.html.twig";
    }
}
