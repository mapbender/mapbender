<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Spatial reference system selector
 * 
 * Changes the map spatial reference system
 * 
 * @author Paul Schmidt
 */
class SrsSelector extends Element {

    public static function getClassTitle() {
        return 'Spatial reference system selector';
    }

    public static function getClassDescription() {
        return "The spatial reference system selector changes the map's
            spatial reference system.";
    }

    public static function getClassTags() {
        return array('spatial', 'reference', 'system', 'selector');
    }

    public function getAssets() {
        return array(
            'js' => array('mapbender.element.srsselector.js',
                'proj4js/proj4js-compressed.js',
//                'proj4js/defs/EPSG4326.js',
//                'proj4js/defs/EPSG25832.js',
//                'proj4js/defs/EPSG25833.js',
                ),
            'css' => array('mapbender.elements.css')
        );
    }

    public static function getDefaultConfiguration() {
        return array(
            "targets" => array(
                "map" => null,
                "coordsdisplay" => "coordinates" ));
    }
    
//    public static function getConfiguration() {
//        $configuration = $this->configuration;
//        return $configuration;
//    }

    public function getWidgetName() {
        return 'mapbender.mbSrsSelector';
    }

    public function render() {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:srsselector.html.twig',
                        array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }
}