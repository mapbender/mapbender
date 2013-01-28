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
        return 'Spatial Reference System Selector';
    }

    public static function getClassDescription() {
        return "The spatial reference system selector changes the map's
            spatial reference system.";
    }

    public static function getClassTags() {
        return array('spatial', 'reference', 'system', 'selector');
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\SrsSelectorAdminType';
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.srsselector.js',
                'proj4js/proj4js-compressed.js'),
            'css' => array('mapbender.elements.css')
        );
    }

    public static function getDefaultConfiguration() {
        return array(
            "tooltip" => "SRS Selector",
            'label' => false,
            "targets" => array(
                "map" => null,
                "coordinatesdisplay" => null));
    }

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