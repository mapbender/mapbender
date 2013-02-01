<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * A legend
 * 
 * Shows legends of the map's layers.
 * 
 * @author Paul Schmidt
 */
class Legend extends Element {

    public static function getClassTitle() {
        return "Legend Object";
    }

    public static function getClassDescription() {
        return "The legend object shows the legend of the map's layers.";
    }

    public static function getClassTags() {
        return array('legend', "dialog");
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.legend.js'
            ),
            'css' => array(
                'mapbender.elements.css'
            )
        );
    }

    public static function getDefaultConfiguration() {
        return array(
            "target" => null,
            "elementType" => null,
            "autoOpen" => false,
            "displayType" => null,
            "tooltip" => "Legend",
            "hiddeemptylayer" => false,
//            "dialogtitle" => "Legend",
            "nolegend" => "No legend available");
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LegendAdminType';
    }
    
    public function getWidgetName() {
        return 'mapbender.mbLegend';
    }

    public function render() {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:legend.html.twig',
                        array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }
}

