<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ApplicationInterface;
use Symfony\Component\HttpFoundation\Response;

class Application implements ApplicationInterface {
	protected $container;
	protected $configuration;
	protected $layer_factories;

	public function __construct($container, $configuration) {
		$this->container = $container;
		$this->configuration = $configuration;
		$this->layer_factories = array();
		
		// A title is required, otherwise it would be hard to select an
		// application in a list
		if(!$this->getTitle()) {
			throw new \Exception('No title set for application');
		}
	}

	public function getTitle() {
		return $this->configuration['title'];
	}

	public function getDescription() {
		return array_key_exists('description', $this->configuration) ? $this->configuration['description'] : NULL;
	}

	public function getTemplate() {
		$template_name = $this->configuration['template'];
		return $this->container->get($template_name);
	}
	
	public function getElements() {
		
	}
		
	public function getLayers() {
		if(!array_key_exists('layers', $this->configuration)) {
			return array();
		}

		$layers = array();
		foreach($this->configuration['layers'] as $name => $configuration) {
			$factory = $this->getLayerFactory($configuration['type']);
			if(!$factory) {
				continue;
			}

			$layers[$name] = $factory->create($name, $configuration);
		}
		print_r($layers);
		die();
		return $layers;
	}
	
	public function render(Response $response = NULL) {
		if($response == NULL) {
			$response = new Response();
		}

		$response->setContent($this->getTemplate()->render(array(
			'title' => $this->getTitle(),
			'layers' => $this->getLayers(),
		)));

		return $response;
	}

	/**
	 * Get the layer factory for the specified type and cache it
	 *
	 * @param string $type The layer class identifier to get the layer factory for
	 * @return LayerFactoryInterface The layer factory
	 */
	protected function getLayerFactory($type) {
		if(array_key_exists($type, $this->layer_factories)) {
			return $this->layer_factories[$type];
		} else {
			$factory = $this->container->get('mapbender.layer_factory.' . $type);
			if($factory) {
				$this->layer_factories[$type] = $factory;
				return $factory;
			}
		}
	}
}

