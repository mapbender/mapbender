<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Element\Type\ResetViewAdminType;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class ResetView extends ButtonLike
{
    public static function getClassTitle(): string
    {
        return 'mb.core.resetView.class.title';
    }

    public static function getClassDescription(): string
    {
        return 'mb.core.resetView.class.description';
    }

    public function getWidgetName(Element $element): string
    {
        return 'MbResetView';
    }

    public static function getType(): string
    {
        return ResetViewAdminType::class;
    }

    /**
     * @inheritdoc
     * @return mixed[]
     */
    public function getRequiredAssets(Element $element): array
    {
        $requirements = parent::getRequiredAssets($element) + [
            'js' => [],
        ];
        $requirements['js'] = \array_merge($requirements['js'], [
            '@MapbenderCoreBundle/Resources/public/elements/MbButton.js',
            '@MapbenderCoreBundle/Resources/public/elements/MbResetView.js',
        ]);
        return $requirements;
    }

    /**
     * @inheritdoc
     * @return mixed[]
     */
    public static function getDefaultConfiguration(): array
    {
        $defaults = array_replace(parent::getDefaultConfiguration(), [
            'resetDynamicSources' => true,
        ]);
        // icon is hard-coded (see twig template)
        unset($defaults['icon']);
        return $defaults;
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/ResetView.html.twig');
        $view->attributes['tabindex'] = '0';
        $this->initializeView($view, $element);
        return $view;
    }
}
