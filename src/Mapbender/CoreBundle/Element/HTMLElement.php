<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Element\Type\HTMLElementAdminType;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\RegionProperties;

/**
 * HTMLElement.
 */
class HTMLElement extends AbstractElementService implements FloatableElement
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
        // no widget constructor, except for non-inline content. For those, reuse MbCopyright widget.
        return $this->isPopup($element) ? 'MbCopyright' : false;
    }

    private function isPopup(Element $element): bool
    {
        return $element->getRegion() === 'content' && !$element->getConfiguration()['openInline'];
    }

    public function getRequiredAssets(Element $element)
    {
        return $this->isPopup($element)
            ? ['js' => ['@MapbenderCoreBundle/Resources/public/elements/MbCopyright.js']]
            : [];
    }

    public static function getType()
    {
        return HTMLElementAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'classes' => '',
            'content' => ''
        );
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration();
        $view = new TemplateView('@MapbenderCore/Element/htmlelement.html.twig');
        $view->attributes['class'] = 'mb-element-htmlelement';
        $view->attributes['data-title'] = $element->getTitle();

        if (!empty($config['classes'])) {
            $view->attributes['class'] .= rtrim(' ' . $config['classes']);
        }
        $view->variables['content'] = $this->isPopup($element)
            ? '<div class="-js-popup-content">' . $config['content'] . '</div>'
            : $config['content'];

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
        return '@MapbenderCore/ElementAdmin/htmlelement.html.twig';
    }

}
