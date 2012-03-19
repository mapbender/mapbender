<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AreaRuler extends Element implements ElementInterface {
    static public function getTitle() {
        return "Area ruler";
    }

    static public function getDescription() {
        return "Ruler to measure area";
    }

    static public function getTags() {
        return array('Ruler', 'Measure');
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.ruler.common.js',
                'mapbender.element.ruler.area.js'
            ),
            'css' => array(
                'mapbender.elements.css'
            )
        );
    }

    public function getConfiguration() {
        $tr = $this->get('translator');
        $opts = $this->configuration;
        $opts['text'] = $this->name;
        $opts['title'] = $tr->trans($opts['title']);
        // Resolve the run-time id of the target widget
        if(array_key_exists('target', $this->configuration)) {
            $elementId = $this->configuration['target'];
            $finalId = $this->application->getFinalId($elementId);
            $opts = array_merge($opts, array('target' => $finalId));
        }
        return array(
            'options' => $opts,
            'init' => 'mbAreaRuler',
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
        return $this->get('templating')->render('MapbenderCoreBundle:Element:measure_dialog.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration,
                'type' => 'area',
                'label' => $this->configuration['title']));
    }
}

