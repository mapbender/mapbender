<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * The Legend class shows legends of the map's layers.
 * 
 * @author Paul Schmidt
 */
class Legend extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "Legend Object";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "The legend object shows the legend of the map's layers.";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array('legend', "dialog");
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.legend.js'
            ), 'css' => array()
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
            "noLegend" => "No legend available",
            "autoOpen" => true,
            "tooltip" => "Legend",
            "checkGraphic" => false,
            "hideEmptyLayers" => true,
            "generateLegendGraphicUrl" => false,
            "showSourceTitle" => true,
            "showLayerTitle" => true,
            "showGrouppedTitle" => true);
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

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
                        'MapbenderCoreBundle:Element:legend.html.twig',
                        array('id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:legend.html.twig';
    }
}

