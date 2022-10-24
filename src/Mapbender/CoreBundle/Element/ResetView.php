<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class ResetView extends ButtonLike
{
    public static function getClassTitle()
    {
        return 'mb.core.resetView.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.resetView.class.description';
    }

    public function getWidgetName(Element $element)
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
    public function getRequiredAssets(Element $element)
    {
        $requirements = parent::getRequiredAssets($element) + array(
            'js' => array(),
        );
        $requirements['js'] = \array_merge($requirements['js'], array(
            '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js',
            '@MapbenderCoreBundle/Resources/public/element/resetView.js',
        ));
        return $requirements;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        $defaults = array_replace(parent::getDefaultConfiguration(), array(
            'resetDynamicSources' => true,
        ));
        // icon is hard-coded (see twig template)
        unset($defaults['icon']);
        return $defaults;
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:ResetView.html.twig');
        $this->initializeView($view, $element);
        return $view;
    }
}
