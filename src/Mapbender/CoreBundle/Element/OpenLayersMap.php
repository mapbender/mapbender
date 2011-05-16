<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\ElementInterface;

class OpenLayersMap implements ElementInterface {
	protected $configuration;
	protected $id;

	public function __construct($id, array $configuration) {
		$this->id = $id;
		$this->configuration = $configuration;
	}

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

	public function getParents() {
		return array();
	}

	public function isContainer() {
		return false;
	}

	public function getId() {
		return $this->id;
	}

	public function getConfiguration() {
		return array(
			'options' => $this->configuration,
			'init' => 'ol_map',
		);
	}

	public function	render(ElementInterface $parentElement = NULL, $block = 'content') {
		if($block == 'content') {
			//TODO: use templating. Then the element template can be overriden by the application
			return '<div id="' . $this->id . '" class="mb-element mb-element-openlayers-map"></div>';
		}
	}

	public function __toString() {
		return $this->render();
	}
}

