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
        return "Map's Legend";
    }

    public static function getClassDescription() {
        return "The legend object shows the legend of the map's layers.";
    }

    public static function getClassTags() {
        return array('legend');
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
            "tooltip" => "Legend",
            "dialogtitle" => "Legend view",
            "nolegend" => "No legend available");
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

