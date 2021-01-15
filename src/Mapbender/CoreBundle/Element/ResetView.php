<?php


namespace Mapbender\CoreBundle\Element;


class ResetView extends BaseButton
{
    // Disable being targetted by a Button
    public static $ext_api = false;

    public static function getClassTitle()
    {
        return 'mb.core.resetView.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.resetView.class.description';
    }

    public function getWidgetName()
    {
        return 'mapbender.resetView';
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ResetViewAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js',
                '@MapbenderCoreBundle/Resources/public/element/resetView.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/button.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        $defaults = parent::getDefaultConfiguration();
        // icon is hard-coded to iconReset (see twig template)
        unset($defaults['icon']);
        return $defaults;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderCoreBundle:Element:ResetView.html.twig";
    }
}
