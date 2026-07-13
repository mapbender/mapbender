<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\ScaleDisplayAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * @author Paul Schmidt
 */
class ScaleDisplay extends AbstractElementService implements FloatableElement
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.scaledisplay.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.scaledisplay.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'title' => self::getClassTitle(),
            'unitPrefix' => false,
            'scalePrefix' => 'mb.core.scaledisplay.label',
            'anchor' => 'right-bottom',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbScaledisplay';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return ScaleDisplayAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/scaledisplay.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbScaledisplay.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/scaledisplay.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/scaledisplay.html.twig');
        $view->attributes['class'] = 'mb-element-scaledisplay';
        $config = $element->getConfiguration() ?: [];
        $view->variables['scalePrefix'] = ArrayUtil::getDefault($config, 'scalePrefix', static::getDefaultConfiguration()['scalePrefix']);
        return $view;
    }
}
