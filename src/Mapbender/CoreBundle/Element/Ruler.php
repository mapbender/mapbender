<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
//use Symfony\Component\DependencyInjection\ContainerInterface;

class Ruler extends Element {
    static public function getClassTitle() {
        return 'Line/Area Ruler';
    }

    static public function getClassDescription() {
        return "Please give me a description";
    }

    static public function getClassTags() {
        return array();
    }

    public function getAssets() {
        return array(
            'js' => array('@MapbenderCoreBundle/Resources/public/mapbender.element.ruler.js'),
            //TODO: Split up
            'css' => array('@MapbenderCoreBundle/Resources/public/mapbender.elements.css'));
    }

    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\RulerAdminType';
    }
    
    public static function getDefaultConfiguration() {
        return array(
            'target' => null,
            'tooltip' => "ruler",
            'type' => null);
    }

    public function getWidgetName() {
        return 'mapbender.mbRuler';
    }

    public function render() {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:measure_dialog.html.twig',
                    array(
                        'id' => $this->getId(),
                        'title' => $this->getTitle(),
                        'configuration' => $this->getConfiguration()));
    }
}

