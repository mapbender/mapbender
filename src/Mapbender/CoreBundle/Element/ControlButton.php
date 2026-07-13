<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Element\Type\ControlButtonAdminType;
use Mapbender\Component\ClassUtil;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity\Element;

class ControlButton extends ButtonLike
{
    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.button.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.controlbutton.class.description";
    }

    public function getWidgetName(Element $element): string
    {
        return 'MbControlButton';
    }

    /**
     * @return mixed[]
     */
    public function getRequiredAssets(Element $element): array
    {
        $requirements = parent::getRequiredAssets($element) + [
            'js' => [],
        ];
        $requirements['js'][] = '@MapbenderCoreBundle/Resources/public/elements/MbButton.js';
        return $requirements;
    }

    public static function getType(): string
    {
        return ControlButtonAdminType::class;
    }

    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/control_button.html.twig';
    }

    public static function getDefaultConfiguration(): array
    {
        return array_replace(parent::getDefaultConfiguration(), [
            'group' => null,
            'target' => null,
        ]);
    }

    public function getView(Element $element): false|TemplateView
    {
        $target = $element->getTargetElement('target');
        if (!$target || !$target->getClass() || !ClassUtil::exists($target->getClass())) {
            return false;
        }

        $view = new TemplateView('@MapbenderCore/Element/control_button.html.twig');
        $this->initializeView($view, $element);

        $config = $element->getConfiguration();
        $view->attributes['data-group'] = $config['group'];

        // Undo / replace parent label and tooltip fallbacks with target title
        $label = $element->getTitle() ?: $target->getTitle();
        if (!$label) {
            /** @var MinimalInterface|string $targetClass */
            $targetClass = $target->getClass();
            $label = $targetClass::getClassTitle();
        }
        $view->variables['label'] = $label;
        $view->attributes['title'] = $config['tooltip'] ?: $label;
        return $view;
    }
}
