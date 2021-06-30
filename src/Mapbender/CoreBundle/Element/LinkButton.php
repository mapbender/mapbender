<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class LinkButton extends ButtonLike
{
    public static function getClassTitle()
    {
        return 'mb.core.linkbutton.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.linkbutton.class.description';
    }

    public function getWidgetName(Element $element)
    {
        // No script
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

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:link_button.html.twig');
        $this->initializeView($view, $element);
        $view->variables['link_target'] = $element->getConfiguration()['click'];
        return $view;
    }
}
