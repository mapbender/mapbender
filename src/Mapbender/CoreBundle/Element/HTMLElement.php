<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Element\Type\HTMLElementAdminType;
use Mapbender\CoreBundle\Entity\Element;

/**
 * HTMLElement. Integrated the copyright element from Mapbender version 5 onwards
 */
class HTMLElement extends AbstractElementService implements FloatableElement
{
    public static function getClassTitle(): string
    {
        return 'mb.core.htmlelement.class.title';
    }

    public static function getClassDescription(): string
    {
        return 'mb.core.htmlelement.class.description';
    }

    public function getWidgetName(Element $element): string|false
    {
        // no widget constructor for inline content
        return $this->isPopup($element) ? 'MbHtmlElement' : false;
    }

    private function isPopup(Element $element): bool
    {
        return $element->getRegion() === 'content' && !($element->getConfiguration()['openInline'] ?? false);
    }

    public function getRequiredAssets(Element $element): array
    {
        return $this->isPopup($element)
            ? ['js' => ['@MapbenderCoreBundle/Resources/public/elements/MbHtmlElement.js']]
            : [];
    }

    public static function getType(): string
    {
        return HTMLElementAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'openInline' => false,
            'autoOpen' => false,
            'content' => null,
            'dontShowAgain' => false,
            'dontShowAgainLabel' => 'mb.core.htmlelement.admin.dontShowAgainDefaultLabel',
            'popupWidth'    => 300,
            'popupHeight' => null,
            'element_icon' => self::getDefaultIcon(),
            'classes' => '',
            'modal' => false,
        ];
    }

    public function getClientConfiguration(Element $element)
    {
        $config = parent::getClientConfiguration($element);
        // Legacy compatibility: The former Copyright element has been merged into the HtmlElement.
        // the former copyright elements always should have the "modal" config property set
        if ($element->getOriginallyDefinedClass() === 'Mapbender\CoreBundle\Element\Copyright') {
            $config['modal'] = true;
        }
        return $config;
    }

    public function getView(Element $element): TemplateView
    {
        $config = $element->getConfiguration();
        $template = $this->isPopup($element)
            ? '@MapbenderCore/Element/htmlelement_popup.html.twig'
            : '@MapbenderCore/Element/htmlelement.html.twig';
        $view = new TemplateView($template);

        $view->attributes['class'] = 'mb-element-htmlelement';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['content'] = $config['content'];
        $view->variables['dontShowAgain'] = $config['dontShowAgain'];
        $view->variables['dontShowAgainLabel'] = $config['dontShowAgainLabel'];
        $view->variables['application'] = $element->getApplication();
        $view->variables['entity'] = $element;

        if (!empty($config['classes'])) {
            $view->attributes['class'] .= rtrim(' ' . $config['classes']);
        }

        /** @see https://doc.mapbender.org/en/functions/misc/html.html for twig variable expectations */
        // Do not cache if content contains any twig expressions or flow control ("{{" or "{%")
        if (str_contains($config['content'], '{')) {
            $view->cacheable = false;
        }
        return $view;
    }

    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/htmlelement.html.twig';
    }

    public static function getDefaultIcon()
    {
        return 'iconLegend';
    }

}
