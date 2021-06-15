<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class ShareUrl extends ButtonLike
{
    public static function getClassTitle()
    {
        return 'mb.core.ShareUrl.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.ShareUrl.class.description';
    }

    public function getWidgetName(Element $element)
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
    public function getRequiredAssets(Element $element)
    {
        $required = parent::getRequiredAssets($element) + array(
            'js' => array(),
            'css' => array(),
            'trans' => array(),
        );
        // Remove / replace base button script
        $required['js'] = array_merge($required['js'], array(
            '@MapbenderCoreBundle/Resources/public/element/mbShareUrl.js',
        ));
        $required['css'] = array_merge($required['css'], array(
            '@MapbenderCoreBundle/Resources/public/element/mbShareUrl.scss',
        ));
        $required['trans'] = array_merge($required['trans'], array(
            'mb.core.ShareUrl.*',
        ));
        return $required;
    }

    public static function getDefaultConfiguration()
    {
        $defaults = parent::getDefaultConfiguration();
        // icon is hard-coded (see twig template)
        unset($defaults['icon']);
        return $defaults;
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:ShareUrl.html.twig');
        parent::initializeView($view, $element);
        $view->attributes['class'] = 'mb-button mb-element-shareurl';
        return $view;
    }
}
