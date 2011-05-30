<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OpenLayersMap extends Element implements ElementInterface {
	public function getTitle() {
		return "Openlayers Map";
	}

	public function getDescription() {
		return "Renders a Openlayers map";
	}

	public function getTags() {
		return array('map', 'OpenLayers');
	}

	public function getAssets() {
		return array(
			'js' => array(
				'bundles/mapbendercore/OpenLayers-2.10/OpenLayers.js',
				'bundles/mapbendercore/OpenLayers_LayerFactory.js',
				'bundles/mapbendercore/Mapbender.Element.OpenLayersMap.js',
			),
			'css' => array(
				'bundles/mapbendercore/OpenLayers-2.10/theme/default/style.css',
				'bundles/mapbendercore/OpenLayers_MapElement.css',
			)
		);
	}

	public function getConfiguration() {
		return array(
			'options' => $this->configuration,
			'init' => 'ol_map',
		);
	}

	public function	render($widget, $block = 'content', ElementInterface $parentElement = NULL) {
		if($block == 'content') {
			//TODO: use templating. Then the element template can be overriden by the application
			return '<div id="' . $this->id . '" class="mb-element mb-element-openlayers-map"></div>';
		}
	}
}

