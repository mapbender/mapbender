<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class Layertree extends Element {
    static public  function getClassTitle() {
        return "Layertree";
    }

    static public function getClassDescription() {
        return "Tree of map's layers";
    }
    
    public function getDescription() {
        return "Shows a treeview of the layers on the map";
    }

    public function getTags() {
        return array('Layertree', 'Layer');
    }
    
    public function getWidgetName() {
        return 'mapbender.mbLayertree';
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LayertreeAdminType';
    }
    
    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.layertree.js'
            ),
            'css' => array(
                'mapbender.element.layertree.css'
            )
        );
    }

    static public function getDefaultConfiguration() {
        return array(
            "target" => null,
            "autoOpen" => false
        );
    }

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

    public function render() {
        return $this->container->get('templating')->render(
            'MapbenderCoreBundle:Element:layertree.html.twig', array(
                'id' => $this->getId(),
                'configuration' => $this->entity->getConfiguration(),
                'title' => $this->getTitle()
                )
            );
    }
}

