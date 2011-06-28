<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Map extends Element implements ElementInterface {
	public function getTitle() {
		return "MapQuery Map";
	}

	public function getDescription() {
		return "Renders a MapQuery map";
	}

	public function getTags() {
		return array('Map', 'MapQuery');
	}

	public function getAssets() {
		return array(
            'js' => array(
                'mapquery/lib/openlayers/OpenLayers.js',
                'mapquery/lib/jquery/jquery.tmpl.js',
                'mapquery/src/jquery.mapquery.core.js',
                'mapbender.element.map.js'
			),
            'css' => array(
                'mapquery/lib/jquery/themes/base/jquery-ui.css',
                'mapbender.element.map.css'
			)
		);
	}

    public function getConfiguration() {
        //TODO: Cherry pick
		return array(
			'options' => $this->configuration,
			'init' => 'mbMap',
		);
	}

    public function	render() {
            return $this->get('templating')->render('MapbenderCoreBundle:Element:map.html.twig', array(
                'id' => $this->id
            ));
	}
}

