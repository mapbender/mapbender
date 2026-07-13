<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\SrsSelectorAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;


/**
 * Spatial reference system selector
 *
 * Changes the map spatial reference system
 *
 * @author Paul Schmidt
 */
class SrsSelector extends AbstractElementService
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.srsselector.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.srsselector.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return SrsSelectorAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbSrsSelector.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/srsselector.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'tooltip' => static::getClassTitle(),
            'label' => false,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbSrsSelector';
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/srsselector.html.twig');
        $config = $element->getConfiguration();
        $view->attributes = [
            'class' => 'mb-element-srsselector',
            'title' => $config['tooltip'] ?: $element->getTitle(),
        ];
        $view->variables = [
            'label' => $config['label'] ? $element->getTitle() : null,
        ];
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/srsselector.html.twig';
    }

}
