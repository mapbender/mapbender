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
            "target" => "map",
            "title" => "Legend",
            "dialogtitle" => "Legend view",
            "nolegend" => "No legend available");
    }
    
//    public static function getConfiguration() {
//        $configuration = $this->configuration;
//        return $configuration;
//    }

    public function getWidgetName() {
        return 'mapbender.mbLegend';
    }

    public function render() {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:legend.html.twig',
                        array(
                            'id' => $this->getId(),
                            'configuration' => $this->getConfiguration()));
    }

//    public function getConfiguration() {
//        $tr = $this->get('translator');
//        $opts = $this->configuration;
//        $opts['text'] = $this->name;
//        $opts['title'] = $tr->trans($opts['title']);
//        $opts['dialogtitle'] = $tr->trans($opts['dialogtitle']);
//        $opts['nolegend'] = $tr->trans($opts['nolegend']);
//        // Resolve the run-time id of the target widget
//        if(array_key_exists('target', $this->configuration)) {
//            $elementId = $this->configuration['target'];
//            $finalId = $this->application->getFinalId($elementId);
//            $opts = array_merge($opts, array('target' => $finalId));
//        }
//        return array(
//            'options' => $opts,
//            'init' => 'mbLegend',
//        );
//    }
//
//    public function httpAction($action) {
//        $response = new Response();
//
//        $data = array(
//            'message' => 'Hello World'
//        );
//        $response->setContent(json_encode($data));
//        $response->headers->set('Content-Type', 'application/json');
//        return $response;
//    }
//
//    public function render() {
//        return $this->get('templating')->render('MapbenderCoreBundle:Element:legend.html.twig', array(
//                'id' => $this->id,
//                'configuration' => $this->configuration,
//                'label' => $this->name));
//    }
}

