<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class ActivityIndicator extends Element implements ElementInterface {
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
                'mapbender.element.activityindicator.js'
            ),
            'css' => array(
                'mapbender.elements.css'
            )
        );
    }

    public function getConfiguration() {
        $opts = $this->configuration;
        return array(
            'options' => $opts,
            'init' => 'mbActivityIndicator',
        );
    }

    public function render() {
        return $this->get('templating')->render('MapbenderCoreBundle:Element:activityindicator.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration));
    }
}

