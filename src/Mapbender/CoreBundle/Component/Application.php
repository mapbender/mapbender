<?php

/**
 * TODO: License
 */

namespace Mapbender\CoreBundle\Component;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetReference;
use Assetic\Asset\FileAsset;
use Assetic\FilterManager;
use Assetic\Asset\StringAsset;
use Assetic\AssetManager;
use Assetic\Factory\AssetFactory;

use Mapbender\CoreBundle\Entity\Application as Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Application is the main Mapbender3 class.
 *
 * This class is the controller for each application instance.
 * The application class will not perform any access checks, this is due to
 * the controller instantiating an application. The controller should check
 * with the configuration entity to get a list of allowed roles and only then
 * decide to instantiate a new application instance based on the configuration
 * entity.
 *
 * @author Christian Wygoda
 */
 class Application {
    /**
     * @var ContainerInterface $container The container
     */
    protected $container;

    /**
     * @var Template $template The application template class
     */
    protected $template;

    /**
     * @var array $elements The elements, ordered by weight
     */
    protected $elements;

    /**
     * @var array $layers The layers
     */
    protected $layers;

    /**
     * @var array $urls Runtime URLs
     */
    protected $urls;

    /**
     * @param ContainerInterface $container The container
     * @param Entity $entity The configuration entity
     * @param array $urls Array of runtime URLs
     */
    public function __construct(ContainerInterface $container,
        Entity $entity, array $urls) {
        $this->container = $container;
        $this->entity = $entity;
        $this->urls = $urls;
    }

    /*************************************************************************
     *                                                                       *
     *                    Configuration entity handling                      *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the configuration entity.
     *
     * @return object $entity
     */
    public function getEntity() {
        return $this->entity;
    }

    /*************************************************************************
     *                                                                       *
     *             Shortcut functions for leaner Twig templates              *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the application ID
     *
     * @return integer
     */
    public function getId() {
        return $this->entity->getId();
    }

    /**
     * Get the application slug
     *
     * @return string
     */
    public function getSlug() {
        return $this->entity->getSlug();
    }

    /**
     * Get the application title
     *
     * @return string
     */
    public function getTitle() {
        return $this->entity->getTitle();
    }

    /**
     * Get the application description
     *
     * @return string
     */
    public function getDescription() {
        return $this->entity->getDescription();
    }

    /*************************************************************************
     *                                                                       *
     *                              Frontend stuff                           *
     *                                                                       *
     *************************************************************************/

    /**
     * Render the application
     *
     * @param string format Output format, defaults to HTML
     * @param boolean $html Whether to render the HTML itself
     * @param boolean $css  Whether to include the CSS links
     * @param boolean $js   Whether to include the JavaScript
     * @return string $html The rendered HTML
     */
    public function render($format = 'html', $html = true, $css = true,
        $js = true) {
        return $this->getTemplate()->render($format, $html, $css, $js);
    }

    /**
     * Get the assets as an AsseticCollection.
     * Filters can be applied later on with the ensureFilter method.
     *
     * @param string $type Can be 'css' or 'js' to indicate which assets to dump
     * @return AsseticFactory
     */
    public function getAssets($type) {
        if($type !== 'css' && $type !== 'js') {
            throw new \RuntimeException('Asset type \'' . $type .
                '\' is unknown.');
        }

        // Add all assets to an asset manager first to avoid duplication
        $assets = new AssetManager();

        if($type === 'js') {
            // Mapbender API
            $file = '@MapbenderCoreBundle/Resources/public/mapbender.application.js';
            $this->addAsset($assets, $type, $file);
            // Translation API
            $file = '@MapbenderCoreBundle/Resources/public/mapbender.trans.js';
            $this->addAsset($assets, $type, $file);
            // WDT fixup
            if($this->container->has('web_profiler.debug_toolbar')) {
                $file = '@MapbenderCoreBundle/Resources/public/mapbender.application.wdt.js';
                $this->addAsset($assets, $type, $file);
            }
        }
        if($type === 'css') {
            $file = '@MapbenderCoreBundle/Resources/public/mapbender.application.css';
            $this->addAsset($assets, $type, $file);
        }

        // Load all elements assets
        foreach($this->getElements() as $region => $elements) {
            foreach($elements as $element) {
                $element_assets = $element->getAssets();
                foreach($element_assets[$type] as $asset) {
                    $this->addAsset($assets, $type,
                        $this->getReference($element, $asset));
                }
            }
        }

        // Load all layer assets
        foreach($this->getLayersets() as $layerset) {
            foreach($layerset->layerObjects as $layer) {
                $layer_assets = $layer->getAssets();
                foreach($layer_assets[$type] as $asset) {
                    $this->addAsset($assets, $type, $this->getReference($layer,
                        $asset));
                }
            }
        }

        // Load the template assets last, so it can easily overwrite element
        // and layer assets for application specific styling for example
        foreach($this->getTemplate()->getAssets($type) as $asset) {
            $file = $this->getReference($this->template, $asset);
            $this->addAsset($assets, $type, $file);
        }

        // Load extra assets given by application
        $extra_assets = $this->getEntity()->getExtraAssets();
        if(is_array($extra_assets) && array_key_exists($type, $extra_assets)) {
            foreach($extra_assets[$type] as $asset) {
                $asset = trim($asset);
                $this->addAsset($assets, $type, $asset);
            }
        }

        // Get all assets out of the manager and into an collection
        $collection = new AssetCollection();
        foreach($assets->getNames() as $name) {
            $collection->add($assets->get($name));
        }

        return $collection;
    }

    private function addAsset($manager, $type, $reference) {
        $locator = $this->container->get('file_locator');
        $file = $locator->locate($reference);

        // This stuff is needed for CSS rewrite. This will use the file contents
        // from the bundle directory, but the path inside the public folder
        // for URL rewrite
        $sourceBase = null;
        $sourcePath = null;
        if($reference[0] == '@') {
            // Bundle name
            $bundle = substr($reference, 1, strpos($reference, '/') - 1);
            // Installation root directory
            $root = dirname($this->container->getParameter('kernel.root_dir'));
            // Path inside the Resources/public folder
            $assetPath = substr($reference,
                strlen('@' . $bundle . '/Resources/public'));

            // Path for the public version
            $public = $root . '/web/bundles/' .
                preg_replace('/bundle$/', '', strtolower($bundle)) .
                $assetPath;

            $sourceBase = '';
            $sourcePath = $public;
        }

        $asset = new FileAsset($file,
            array(),
            $sourceBase,
            $sourcePath);
        $name = str_replace(array('@', '/', '.', '-'), '__', $reference);
        $manager->set($name, $asset);
    }

    /**
     * Get the configuration (application, elements, layers) as an StringAsset.
     * Filters can be applied later on with the ensureFilter method.
     *
     * @return StringAsset The configuration asset object
     */
    public function getConfiguration() {
        $configuration = array();

        $configuration['application'] = array(
            'title' => $this->entity->getTitle(),
            'urls' => $this->urls,
            'slug' => $this->getSlug());

        // Get all element configurations
        $configuration['elements'] = array();
        foreach($this->getElements() as $region => $elements) {
            foreach($elements as $element) {
                $configuration['elements'][$element->getId()] = array(
                    'init' => $element->getWidgetName(),
                    'configuration' => $element->getConfiguration());
            }
        }

        // Get all layer configurations
        $configuration['layersets'] = array();
        foreach($this->getLayersets() as $layerset) {
            $cnfiguration['layersets'][$layerset->getId()] = array();
            foreach($layerset->layerObjects as $layer) {
                $configuration['layersets'][$layerset->getId()][$layer->getId()] = array(
                    'type' => $layer->getType(),
                    'title' => $layer->getTitle(),
                    'configuration' => $layer->getConfiguration());
            }
        }

        // Convert to asset
        $asset = new StringAsset(json_encode((object) $configuration));
        return $asset->dump();
    }

    /**
     * Return the element with the given id
     *
     * @param string $id The element id
     * @return Element
     */
    public function getElement($id) {
        $elements = $this->getElements();
        foreach($elements as $region => $element_list) {
            foreach($element_list as $element) {
                if($id === $element->getId()) {
                    return $element;
                }
            }
        }
        throw new NotFoundHttpException();
    }

    /**
     * Build an Assetic reference path from a given objects bundle name(space)
     * and the filename/path within that bundles Resources/public folder
     *
     * @param object $object
     * @param string $file
     * @return string
     */
    private function getReference($object, $file) {
        // If it starts with an @ we assume it's already an assetic reference
        if($file[0] !== '@') {
            $namespaces = explode('\\', get_class($object));
            $bundle = sprintf('%s%s', $namespaces[0], $namespaces[1]);
            return sprintf('@%s/Resources/public/%s', $bundle, $file);
        } else {
            return $file;
        }
    }

    /**
     * Get template object
     *
     * @return Template
     */
    public function getTemplate() {
        if($this->template === null) {
            $template = $this->entity->getTemplate();
            $this->template = new $template($this->container, $this);
        }
        return $this->template;
    }

    /**
     * Get elements, optionally by region
     *
     * @param string $region Region to get elements for. If null, all elements
     * are returned.
     * @return array
     */
    public function getElements($region = null) {
        if($this->elements === null) {
            // Set up all elements (by region)
            $this->elements = array();
            foreach($this->entity->getElements() as $entity) {
                $class = $entity->getClass();
                $element = new $class($this, $this->container, $entity);
                $r = $entity->getRegion();

                if(!array_key_exists($r, $this->elements)) {
                    $this->elements[$r] = array();
                }
                $this->elements[$r][] = $element;
            }

            // Sort each region element's by weight
            foreach($this->elements as $r => $elements) {
                usort($elements, function($a, $b) {
                    $wa = $a->getEntity()->getWeight();
                    $wb = $b->getEntity()->getWeight();
                    if($wa == $wb) {
                        return 0;
                    }
                    return ($wa < $wb) ? -1 : 1;
                });
            }
        }

        if($region) {
            return array_key_exists($region, $this->elements) ?
                $this->elements[$region] : array();
        } else {
            return $this->elements;
        }
    }

    /**
     * @TODO: Needs documentation
     */
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

    public function getLayersets() {
        if($this->layers === null) {

            // Set up all elements (by region)
            $this->layers = array();
            foreach($this->entity->getLayersets() as $layerset) {
                $layerset->layerObjects = array();
                foreach($layerset->getLayers() as $entity) {
                    if($this->getEntity()->getSource() === Entity::SOURCE_YAML) {
                        $class = $entity->getClass();
                        $layer = new $class($this->container, $entity);
                        $layerset->layerObjects[] = $layer;
                    } else {
                        //print_r(get_class($entity->getSourceInstance()));die;
                        $layerset->layerObjects[] = $entity->getSourceInstance();
                    }
                }
                $this->layers[$layerset->getId()] = $layerset;
            }

        }
        return $this->layers;
    }
 }

