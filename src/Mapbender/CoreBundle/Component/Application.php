<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ApplicationInterface;
use Symfony\Component\HttpFoundation\Response;

class Application implements ApplicationInterface {
	protected $container;
	protected $configuration;
	protected $regions;
	protected $layersets;
	protected $template;
	protected $element_id_template;

	public function __construct($container, $configuration) {
		$this->container = $container;
		$this->configuration = $configuration;
		
		// A title is required, otherwise it would be hard to select an
		// application in a list
		if(!$this->getTitle()) {
			throw new \Exception('No title set for application');
		}

		$this->element_id_template = "element-%d";
	}

	public function getTitle() {
		return $this->configuration['title'];
	}

	public function getDescription() {
		return array_key_exists('description', $this->configuration) ? $this->configuration['description'] : NULL;
	}

	public function getTemplate() {
		if(!$this->template) {
			$this->loadTemplate();
		}
		return $this->template;
	}
	
	public function getLayersets() {
		return $this->layersets;
	}
		
	public function getElement($id) {
		$template_metadata = $this->getTemplate()->getMetadata();
		$counter = 0;
		foreach($template_metadata['regions'] as $region) {
			// Only iterate over regions defined in the app
			if(!array_key_exists($region, $this->configuration['elements'])) {
				continue;
			}
			foreach($this->configuration['elements'][$region] as $name => $element) {
				$element_id = sprintf($this->element_id_template, $counter++);
				if($element_id == $id) {
					$class = $element['class'];
					unset($element['class']);
					return new $class($id, $name, $element, $this->container);
				}
			}
		}
		return NULL;
	}

	public function render(Response $response = NULL) {
		if($response == NULL) {
			$response = new Response();
		}

		$this->loadElements();
		$this->loadLayers();

		$layersets = array();
		foreach($this->layersets as $title => $layers) {
			$layersets[$title] = array();
			foreach($layers as $layer) {
				$layersets[$title][] = $layer->render();
			}
		}

		//TODO: This is a little weird, to use the asset helper to get the base path...?
		$base_path = $this->container->get('templating.helper.assets')->getBasePath();
		
		// Get all assets we need to include
		// First the application and template assets
		$js = array('bundles/mapbendercore/Mapbender.Application.js');
		$template_metadata = $this->getTemplate()->getMetadata();
		$css = array_merge(array(), $template_metadata['css']);
		$js  = array_merge($js, $template_metadata['js']);
		// Then merge in all element assets
		// We also grab the element confs here
		$elements_confs = array();
		foreach($this->regions as $region => $elements) {
			foreach($elements as $element) {
				$assets = $element->getAssets();
				if(array_key_exists('css', $assets)) {
					$css = array_merge($css, $assets['css']);
				}
				if(array_key_exists('js', $assets)) {
					$js  = array_merge($js,  $assets['js']);
				}
				$element_confs[$element->getId()] = $element->getConfiguration();
			}
		}	

		$configuration = array(
			'title' => $this->getTitle(),
			'layersets' => $layersets,
			'elements' => $element_confs,
			'srs' => $this->configuration['srs'],
			'basePath' => $base_path,
			'slug' => 'main', //TODO: Make dynamic
			'extents' => $this->configuration['extents'],			
		);


		$response->setContent($this->getTemplate()->render(array(
			'title' => $this->getTitle(),
			'configuration' => "Mapbender = {}; Mapbender.configuration = " . json_encode($configuration),
			'assets' => array(
				'css' => $css,
				'js' => $js),
			'regions' => $this->regions
		)));
		
		return $response;
	}

	/**
	 * Get the layer factory for the specified type and cache it
	 *
	 * @param string $type The layer class identifier to get the layer factory for
	 * @return LayerFactoryInterface The layer factory
	 */
	/*
	protected function getLayerFactory($type) {
		if(array_key_exists($type, $this->layer_factories)) {
			return $this->layer_factories[$type];
		} else {
			//TODO: Search the services tagged mapbender.layer_factory for given type
			$factory = $this->container->get('mapbender.' . $type . '.layer_factory');
			if($factory) {
				$this->layer_factories[$type] = $factory;
				return $factory;
			}
		}
	}
	*/

	/**
	 * Load the template
	 */
	private function loadTemplate() {
		$templating = $this->container->get('templating');
		$this->template = new $this->configuration['template']($templating);
	}

	/**
	 * Using the configuration, load (instantiate) all elements defined for the application
	 */
	private function loadElements() {
		$template_metadata = $this->getTemplate()->getMetadata();
		$this->elements = array();
		$counter = 0;
		foreach($template_metadata['regions'] as $region) {
			// Only iterate over regions defined in the app
			if(!array_key_exists($region, $this->configuration['elements'])) {
				continue;
			}
			foreach($this->configuration['elements'][$region] as $name => $element) {
				// Extract and unset class, so we can use the remains as configuration
				$class = $element['class'];
				unset($element['class']);
				$id = sprintf($this->element_id_template, $counter++);
				$this->regions[$region][] = new $class($id, $name, $element, $this->container);
			}
		}
	}

	/**
	 * Using the configuration, load (instantiate all layers defined for the application
	 */
	private function loadLayers() {
		$this->layersets = array();
		foreach($this->configuration['layersets'] as $name => $layers) {
			$this->layersets[$name] = array();
			foreach($layers as $title => $layer) {
				//Extract and unset class, so we can use the remains as configuration
				$class = $layer['class'];
				unset($layer['class']);
				$this->layersets[$name][] = new $class($title, $layer);
			}
		}
	}
}

