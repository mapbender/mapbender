<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Element\Type\LinkButtonAdminType;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class LinkButton extends ButtonLike
{
    public static function getClassTitle(): string
    {
        return 'mb.core.linkbutton.class.title';
    }

    public static function getClassDescription(): string
    {
        return 'mb.core.linkbutton.class.description';
    }

    public function getWidgetName(Element $element): bool
    {
        // No script
        return false;
    }

    public static function getDefaultConfiguration(): array
    {
        return array_replace(parent::getDefaultConfiguration(), [
            'click' => null,
            'target' => '_blank',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return LinkButtonAdminType::class;
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/link_button.html.twig');
        $this->initializeView($view, $element);
        $configuration = $element->getConfiguration();
        $view->variables['link_href'] = $configuration['click'];
        $view->variables['link_target'] = $configuration['target'] ?? '_blank';
        return $view;
    }
}
