<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\ScaleBarAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * @author Paul Schmidt
 */
class ScaleBar extends AbstractElementService implements ConfigMigrationInterface, FloatableElement
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.scalebar.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.scalebar.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'title' => 'Scale Bar',
            'maxWidth' => 200,
            'anchor' => 'right-bottom',
            'units' => "km",
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbScalebar';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return ScaleBarAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/scalebar.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbScalebar.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/scalebar.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/scalebar.html.twig');
        $view->attributes['class'] = 'mb-element-scaleline smallText';
        $config = $element->getConfiguration() ?: [];
        $maxWidth = \intval(ArrayUtil::getDefault($config, 'maxWidth', null) ?: static::getDefaultConfiguration()['maxWidth']);
        $view->attributes['style'] = "width: auto; min-width: {$maxWidth}px;";
        return $view;
    }

    public static function updateEntityConfig(Element $entity): void
    {
        $config = $entity->getConfiguration();
        if (!empty($config['units'])) {
            // demote legacy multi-units array to scalar
            if (\is_array($config['units'])) {
                // use first value
                $vals = \array_values($config['units']);
                $config['units'] = $vals[0];
            }
        } else {
            // Drop falsy / empty array values. Defaults will be used automatically.
            unset($config['units']);
        }
        $entity->setConfiguration($config);
    }
}
