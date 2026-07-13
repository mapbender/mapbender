<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\LegendAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\StaticView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;

/**
 * The Legend class shows legends of the map's layers.
 *
 * @author Paul Schmidt
 */
class Legend extends AbstractElementService implements ConfigMigrationInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.legend.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.legend.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/LegendEntry.js',
                '@MapbenderCoreBundle/Resources/public/elements/MbLegend.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/legend.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            "autoOpen" => true,
            "showSourceTitle" => true,
            "showLayerTitle" => true,
            "showGroupedLayerTitle" => true,
            "element_icon" => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return LegendAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbLegend';
    }

    public function getView(Element $element): StaticView
    {
        $view = new StaticView('');
        $view->attributes['class'] = 'mb-element-legend';
        $view->attributes['data-title'] = $element->getTitle();
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/legend.html.twig';
    }

    public static function updateEntityConfig(Element $entity): void
    {
        $config = $entity->getConfiguration() ?: [];
        if (!isset($config['showGroupedLayerTitle'])) {
            $defaults = static::getDefaultConfiguration();
            if (isset($config['showGrouppedTitle'])) {
                $config['showGroupedLayerTitle'] = !!$config['showGrouppedTitle'];
            } else {
                $config['showGroupedLayerTitle'] = $defaults['showGroupedLayerTitle'];
            }
        }
        unset($config['showGrouppedTitle']);
        $entity->setConfiguration($config);
    }

    public static function getDefaultIcon(): string
    {
        return 'iconLegend';
    }
}
