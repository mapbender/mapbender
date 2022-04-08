<?php
namespace Mapbender\CoreBundle\Element;

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
    public static function getClassTitle()
    {
        return "mb.core.legend.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.legend.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.legend.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/legend.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "autoOpen" => true,
            "showSourceTitle" => true,
            "showLayerTitle" => true,
            "showGroupedLayerTitle" => true,
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LegendAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbLegend';
    }

    public function getView(Element $element)
    {
        $view = new StaticView('');
        $view->attributes['class'] = 'mb-element-legend';
        $view->attributes['data-title'] = $element->getTitle();
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:legend.html.twig';
    }

    public static function updateEntityConfig(Element $entity)
    {
        $config = $entity->getConfiguration() ?: array();
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
}
