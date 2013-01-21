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

    public function getDescription() {
        return "Shows a treeview of the layers on the map";
    }

    public function getTags() {
        return array();
    }
    
    public function getWidgetName() {
        return 'mapbender.mbLayertree';
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
        );
    }

    public function httpAction($action) {
        $response = new Response();

        $data = array(
            'message' => 'Hello World'
        );
        $response->setContent(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

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

