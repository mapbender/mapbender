<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LineRuler extends Element implements ElementInterface {
    public function getTitle() {
        return "Please give me a title";
    }

    public function getDescription() {
        return "Please give me a description";
    }

    public function getTags() {
        return array();
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.ruler.common.js',
                'mapbender.element.lineruler.js'
            ),
            'css' => array(
                'mapbender.elements.css'
            )
        );
    }

    public function getConfiguration() {
        $opts = $this->configuration;
        $opts['text'] = $this->name;
        // Resolve the run-time id of the target widget
        if(array_key_exists('target', $this->configuration)) {
            $elementId = $this->configuration['target'];
            $finalId = $this->application->getFinalId($elementId);
            $opts = array_merge($opts, array('target' => $finalId));
        }
        return array(
            'options' => $opts,
            'init' => 'mbLineRuler',
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
        return $this->get('templating')->render('MapbenderCoreBundle:Element:button.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration,
                'label' => $this->name));
    }
}

