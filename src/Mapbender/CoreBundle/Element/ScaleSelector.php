<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * A ScaleSelector
 * 
 * Displays and changes a map scale.
 * 
 * @author Paul Schmidt
 */
class ScaleSelector extends Element {
    
    public static function getClassTitle() {
        return "Scale selector";
    }

    public static function getClassDescription() {
        return "Displays and changes a map scale.";
    }

    public static function getClassTags() {
        return array('scale', 'selector');
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.scaleselector.js'
            ),
            'css' => array(
                'mapbender.elements.css'
            )
        );
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleSelectorAdminType';
    }
    
    public static function getDefaultConfiguration() {
        return array(
            "target" => null,
            "tooltip" => "Scale");
    }

    public function getWidgetName() {
        return 'mapbender.mbScaleSelector';
    }

    public function render() {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:scaleselector.html.twig',
                        array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }
}

