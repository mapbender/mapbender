<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;

/**
 * The Legend class shows legends of the map's layers.
 * 
 * @author Paul Schmidt
 */
class Legend extends Element implements ConfigMigrationInterface
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
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.legend.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/legend.scss',
            ),
            'trans' => array(
                'MapbenderCoreBundle:Element:legend.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "elementType" => null,
            "displayType" => null,
            "autoOpen" => true,
            "tooltip" => "Legend",
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
    public function getWidgetName()
    {
        return 'mapbender.mbLegend';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:legend.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(), array(
            'id' => $this->getId(),
            "title" => $this->getTitle(),
            'configuration' => $this->getConfiguration(),
        ));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:legend.html.twig';
    }

    public static function updateEntityConfig(Entity\Element $entity)
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
