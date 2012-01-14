<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Toc extends Element implements ElementInterface {
    static public function getTitle() {
        return "Please give me a title";
    }

    static public function getDescription() {
        return "Please give me a description";
    }

    static public function getTags() {
        return array('TOC', 'Table of Contents');
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.toc.js'
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
            'init' => 'mbToc',
        );
    }

    public function render() {
        return $this->get('templating')->render('MapbenderCoreBundle:Element:toc.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration,
                'label' => $this->configuration['title']));
    }
}

