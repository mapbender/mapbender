<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\CoordinatesDisplayAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Entity\Element;

/**
 * Coordinates display
 *
 * Displays the mouse coordinates
 *
 * @author Paul Schmidt
 * @author Christian Wygoda
 */
class CoordinatesDisplay extends AbstractElementService implements FloatableElement
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.coordinatesdisplay.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.coordinatesdisplay.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return CoordinatesDisplayAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbCoordinatesDisplay.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/coordinatesdisplay.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'anchor' => 'right-bottom',
            'label' => false,
            'numDigits' => 2,
            'empty' => 'x= - y= -',
            'prefix' => 'x= ',
            'separator' => ' y= ',
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbCoordinatesDisplay';
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/coordinatesdisplay.html.twig');
        $view->attributes['class'] = 'mb-element-coordsdisplay';
        $config = $element->getConfiguration();
        $view->variables['label'] = $config['label']
            ? ($element->getTitle() ?: static::getClassTitle())
            : false
        ;
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/coordinatesdisplay.html.twig';
    }

    public static function getDefaultIcon(): string
    {
        return 'iconCoordinates';
    }
}
