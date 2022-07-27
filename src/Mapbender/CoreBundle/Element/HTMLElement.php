<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

/**
 * HTMLElement.
 */
class HTMLElement extends AbstractElementService
{
    public static function getClassTitle()
    {
        return 'mb.core.htmlelement.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.htmlelement.class.description';
    }

    public function getWidgetName(Element $element)
    {
        // no script constructor
        return false;
    }

    public function getRequiredAssets(Element $element)
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\HTMLElementAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'classes' => 'html-element-inline',
            'content' => ''
        );
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration();
        $view = new TemplateView('MapbenderCoreBundle:Element:htmlelement.html.twig');
        $view->attributes['class'] = 'mb-element-htmlelement';
        $view->attributes['data-title'] = $element->getTitle();

        if (!empty($config['classes'])) {
            $view->attributes['class'] .= rtrim(' ' . $config['classes']);
        }
        $view->variables['content'] = $config['content'];
        /** @see https://doc.mapbender.org/en/functions/misc/html.html for twig variable expectations */
        $view->variables['application'] = $element->getApplication();
        $view->variables['entity'] = $element;
        // Do not cache if content contains any twig expressions or flow control ("{{" or "{%")
        if (false !== strpos($config['content'], '{')) {
            $view->cacheable = false;
        }
        return $view;
    }

    public function getClientConfiguration(Element $element)
    {
        // Nothing. No script.
        return array();
    }

    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:htmlelement.html.twig';
    }
}
