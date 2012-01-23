<?php

namespace Acme\DemoBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MapBox extends Element implements ElementInterface {
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
                '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js',
                'mapbender.element.mapbox.js'
            ),
            'css' => array()
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
            'init' => 'mbMapBox',
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

    /**
     * If you want a custom button template, copy the button template from the
     * CoreBundle to your own bundle as a starter.
     */
    public function render() {
        return $this->get('templating')->render('MapbenderCoreBundle:Element:button.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration,
                'label' => $this->configuration['title']));
    }
}

