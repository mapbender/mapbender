<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ApplicationInterface;
use Symfony\Component\HttpFoundation\Response;

class Application implements ApplicationInterface {
    protected $container;
    protected $slug;
	protected $configuration;
	protected $regions;
	protected $layersets;
	protected $template;
	protected $element_id_template;

	public function __construct($container, $slug, $configuration) {
        $this->container = $container;
        $this->slug = $slug;
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

		$base_path = $this->get('request')->getBaseUrl();

		// Get all assets we need to include
        // First the application and template assets
        $js = array();
        $css = array();
        $baseDir = $this->getBaseDir($this);
        $js[] = $baseDir . '/mapbender.application.js';
        $css[] = $baseDir . '/mapbender.application.css';

        $template = $this->getTemplate();
        $baseDir = $this->getBaseDir($template);
        $template_metadata = $this->getTemplate()->getMetadata();
        foreach($template_metadata['css'] as $asset) {
            $css[] = $baseDir . '/' . $asset;
        }
        foreach($template_metadata['js'] as $asset) {
            $js[] = $baseDir . '/' . $asset;
        }

		// Then merge in all element assets
        // We also grab the element confs here
		$elements_confs = array();
		foreach($this->regions as $region => $elements) {
            foreach($elements as $element) {
                $baseDir = $this->getBaseDir($element);

                $assets = $element->getAssets();
                if(array_key_exists('css', $assets)) {
                    foreach($assets['css'] as $asset) {
                        $css[] = $baseDir . '/' . $asset;
                    }
				}

                if(array_key_exists('js', $assets)) {
                    foreach($assets['js'] as $asset) {
                        $js[] = $baseDir . '/' . $asset;
                    }
				}

                $element_confs[$element->getId()] = array_merge(
                    $element->getConfiguration(),
                    array('name' => $element->getName()));
			}
        }

        foreach($this->layersets as $layerset) {
            foreach($layerset as $layer) {
                $baseDir = $this->getBaseDir($layer);

                $assets = $layer->getAssets();
                if(array_key_exists('css', $assets)) {
                    foreach($assets['css'] as $asset) {
                        $css[] = $baseDir . '/' . $asset;
                    }
				}

                if(array_key_exists('js', $assets)) {
                    foreach($assets['js'] as $asset) {
                        $js[] = $baseDir . '/' . $asset;
                    }
				}
            }
        }

        try {
            $wdt = $this->get('web_profiler.debug_toolbar');
            $baseDir = $this->getBaseDir($this);
            $js[] = $baseDir . '/mapbender.application.wdt.js';
        } catch(\Exception $e) {
            // Silently ignore...
        }

		$configuration = array(
			'title' => $this->getTitle(),
			'layersets' => $layersets,
			'elements' => $element_confs,
			'srs' => $this->configuration['srs'],
            'basePath' => $base_path,
            'elementPath' => sprintf('%s/application/%s/element/', $base_path, $this->slug),
			'slug' => $this->slug,
            'extents' => $this->configuration['extents'],
            'proxies' => array(
                'open' => $this->get('router')->generate('mapbender_proxy_open'),
                'secure' => $this->get('router')->generate('mapbender_proxy_secure')
            )
		);

		$response->setContent($this->getTemplate()->render(array(
			'title' => $this->getTitle(),
			'configuration' => "Mapbender = {}; Mapbender.configuration = " . json_encode($configuration),
			'assets' => array(
				'css' => array_unique($css),
				'js' => array_unique($js)),
			'regions' => $this->regions
		)));

		return $response;
	}

    private function getBaseDir($object) {
        $namespaces = explode('\\', get_class($object));
        $bundle = sprintf('%s%s', $namespaces[0], $namespaces[1]);
        // see Symfony\FrameWorkBundle\Command\AssetsInstallCommand, line 77
        $baseDir = sprintf('bundles/%s', preg_replace('/bundle$/', '', strtolower($bundle)));
        return $baseDir;
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
		$templating = $this->get('templating');
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
			$this->regions[$region] = array();
			// Only iterate over regions defined in the app
			if(!array_key_exists($region, $this->configuration['elements'])) {
				continue;
			}
			foreach($this->configuration['elements'][$region] as $name => $element) {
				// Extract and unset class, so we can use the remains as configuration
				$class = $element['class'];
				unset($element['class']);
				$id = sprintf($this->element_id_template, $counter++);
				$this->regions[$region][] = new $class($id, $name, $element, $this);
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

	/**
	 * Access services via the container
	 */
	 public function get($what) {
	   return $this->container->get($what);
	 }

     /**
      * Get final (CSS) id for element by database id
      */
     public function getFinalId($elementId) {
       foreach($this->regions as $region) {
         foreach($region as $element) {
           if($element->getName() === $elementId) {
             return $element->getId();
           }
         }
       }
       return NULL;
     }

     /**
      * Get a list of roles allowed to access this application
      */
     public function getRoles() {
        if(!isset($this->configuration['roles']))
            return array('IS_AUTHENTICATED_ANONYMOUSLY');
        $roles = $this->configuration['roles'];
        if(is_string($roles)) {
            return array($roles);
        } else {
            return $roles;
        }
     }
}

