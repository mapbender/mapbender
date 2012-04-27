<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\ApplicationInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
        $user = $this->container->get('security.context')->getToken()->getUser();
        foreach($template_metadata['regions'] as $region) {
            // Only iterate over regions defined in the app
            if(!array_key_exists($region, $this->configuration['elements'])) {
                continue;
            }
            foreach($this->configuration['elements'][$region] as $name => $element) {
                // check if the roles set
                if(isset($element['roles'])){
                    $allow = false;
                    foreach ($user->getRoles() as $role) {
                        // check if the user authorized
                        if(in_array($role, $element['roles'])){
                            $allow = true;
                            break;
                        }
                    }
                    if(!$allow){
                        continue;
                    }
                }
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

    public function render($_parts = array('css', 'html', 'js', 'configuration'), $_format = 'html') {
        $this->loadLayers();
        $this->loadElements();

        $layersets = array();
        foreach($this->layersets as $title => $layers) {
            $layersets[$title] = array();
            foreach($layers as $layer) {
                $layersets[$title][] = $layer->render();
            }
        }

        $base_path = $this->get('request')->getBaseUrl();

        $config = $this->getAssets();

        $configuration = array(
            'title' => $this->getTitle(),
            'layersets' => $layersets,
            'elements' => $config['element_confs'],
            'basePath' => $base_path,
            'assetPath' => rtrim($this->get('templating.helper.assets')->getUrl('.'), '.'),
            'elementPath' => sprintf('%s/application/%s/element/', $base_path, $this->slug),
            'transPath' => $this->get('router')->generate('mapbender_core_translation_transtext'),
            'slug' => $this->slug,
            'proxies' => array(
                'open' => $this->get('router')->generate('mapbender_proxy_open'),
                //'secure' => $this->get('router')->generate('mapbender_proxy_secure')
            )
        );

        $cssUrl = $this->get('router')->generate('mapbender_core_application_assets', array(
            'slug' => $this->slug,
            'type' => 'css'));
        $jsUrl = $this->get('router')->generate('mapbender_core_application_assets', array(
            'slug' => $this->slug,
            'type' => 'js'));

        return $this->getTemplate()->render(array(
            'configuration' => $configuration,
            'assets' => array(
                'js' => $jsUrl,
                'css' => $cssUrl),
            'regions' => $this->regions
        ), $_parts, $_format);
    }

    public function getAssets() {
        $this->loadLayers();
        $this->loadElements();

        // Get all assets we need to include
        // First the application and template assets
        $js = array();
        $css = array();
        // load mapbender.translate
        $js[] = $this->getReference($this, 'mapbender.trans.js');
        $template = $this->getTemplate();
        $template_metadata = $this->getTemplate()->getMetadata();
        foreach($template_metadata['css'] as $asset) {
            $css[] = $this->getReference($template, $asset);
        }
        foreach($template_metadata['js'] as $asset) {
            $js[] = $this->getReference($template, $asset);
        }

        // Then merge in all element assets
        // We also grab the element confs here
        $element_confs = array();
        foreach($this->regions as $region => $elements) {
            foreach($elements as $element) {
                $assets = $element->getAssets();
                if(array_key_exists('css', $assets)) {
                    foreach($assets['css'] as $asset) {
                        $css[] = $this->getReference($element, $asset);
                    }
                }

                if(array_key_exists('js', $assets)) {
                    foreach($assets['js'] as $asset) {
                        $js[] = $this->getReference($element, $asset);
                    }
                }

                $element_confs[$element->getId()] = array_merge(
                    $element->getConfiguration(),
                    array('name' => $element->getName()));
            }
        }

        foreach($this->layersets as $layerset) {
            foreach($layerset as $layer) {
                $assets = $layer->getAssets();
                if(array_key_exists('css', $assets)) {
                    foreach($assets['css'] as $asset) {
                        $css[] = $this->getReference($layer, $asset);
                    }
                }

                if(array_key_exists('js', $assets)) {
                    foreach($assets['js'] as $asset) {
                        $js[] = $this->getReference($layer, $asset);
                    }
                }
            }
        }

        $js[] = $this->getReference($this, 'mapbender.application.js');
        $css[] = $this->getReference($this, 'mapbender.application.css');

        try {
            $wdt = $this->get('web_profiler.debug_toolbar');
            $js[] = $this->getReference($this, 'mapbender.application.wdt.js');
        } catch(\Exception $e) {
            // Silently ignore...
        }

        return array(
            'element_confs' => $element_confs,
            'css' => array_values(array_unique($css)),
            'js' => array_values(array_unique($js))
        );
    }

    private function getBaseDir($object) {
        $namespaces = explode('\\', get_class($object));
        $bundle = sprintf('%s%s', $namespaces[0], $namespaces[1]);
        // see Symfony\FrameWorkBundle\Command\AssetsInstallCommand, line 77
        $baseDir = sprintf('@%s/Resources/public', $bundle);
        //$baseDir = sprintf('bundles/%s', preg_replace('/bundle$/', '', strtolower($bundle)));
        return $baseDir;
    }

    private function getReference($object, $file) {
        if($file[0] !== '@') {
            $namespaces = explode('\\', get_class($object));
            $bundle = sprintf('%s%s', $namespaces[0], $namespaces[1]);
            return sprintf('@%s/Resources/public/%s', $bundle, $file);
        } else {
            return $file;
        }
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
        $user = $this->container->get('security.context')->getToken()->getUser();
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
                // check if the roles set
                $user_roles = $user instanceof UserInterface ? $user->getRoles() : array();
                if(isset($element['roles'])){
                    $allow = false;
                    foreach ($user_roles as $role) {
                        // check if the user authorized
                        if(in_array($role, $element['roles'])){
                            $allow = true;
                            break;
                        }
                    }
                    if(!$allow){
                        continue;
                    }
                }
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
        foreach($this->configuration['layersets'] as $layersetId => $layers) {
            $this->layersets[$layersetId] = array();
            foreach($layers as $layerId => $layer) {
                //Extract and unset class, so we can use the remains as configuration
                $class = $layer['class'];
                unset($layer['class']);
                $this->layersets[$layersetId][] = new $class($layersetId, $layerId, $layer, $this);
            }
        }
    }
    
    public function reloadLayers($layersetId, $layeridToRemove, $layersToLoad) {
        // remove old layer configuration
        if(isset($this->configuration['layersets'][$layersetId][$layeridToRemove])){
            unset($this->configuration['layersets'][$layersetId][$layeridToRemove]);
        }
        // create a new layer configuration
        $newLayers = array();
        foreach ($this->layersets[$layersetId] as $layersAtLs) {
             if($layersAtLs->getLayerId() == $layeridToRemove) {
                 foreach ($layersToLoad as $layer_ToLoad) {
                     $class = $layer_ToLoad['loaderClass'];
                     $layerL = new $class(
                             $layersetId,
                             $layer_ToLoad['layerId'],
                             array("class" => $layer_ToLoad['loaderClass']),
                             $this);
                     $layerL->loadLayer();
                     $this->configuration['layersets'][$layersetId][$layer_ToLoad['layerId']] = $layerL->getConfiguration();
                     $newLayers[] = $layerL;
                 }
            } else {
                $newLayers[] = $layersAtLs;
            }
        }
        if(isset($this->layersets[$layersetId])){
            unset($this->layersets[$layersetId]);
        }
        $this->layersets[$layersetId] = $newLayers;
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

     /*
      * Get a container parameter
      */
     public function getParameter($key) {
        return $this->container->getParameter($key);
     }

     public function getSlug() {
        return $this->slug;
     }
}

