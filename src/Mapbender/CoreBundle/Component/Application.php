<?php

/**
 * TODO: License
 */
namespace Mapbender\CoreBundle\Component;

use Assetic\Asset\AssetReference;
use Assetic\Asset\FileAsset;
use Assetic\FilterManager;
use Assetic\Asset\StringAsset;
use Assetic\Factory\AssetFactory;
use Mapbender\CoreBundle\Entity\Application as Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

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
class Application
{
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
    public function __construct(ContainerInterface $container, Entity $entity,
        array $urls)
    {
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
    public function getEntity()
    {
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
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get the application slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->entity->getSlug();
    }

    /**
     * Get the application title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

    /**
     * Get the application description
     *
     * @return string
     */
    public function getDescription()
    {
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
        $js = true, $trans = true)
    {
        return $this->getTemplate()->render($format, $html, $css, $js, $trans);
    }

    static public function listAssets()
    {
        $assets = array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/stubs.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.application.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.model.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.application.wdt.js',
                ),
            'css' => array(
            ),
            'trans' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
            ));
        return $assets;
    }

    /**
     * Get the assets as an AsseticCollection.
     * Filters can be applied later on with the ensureFilter method.
     *
     * @param string $type Can be 'css' or 'js' to indicate which assets to dump
     * @return AsseticFactory
     */
    public function getAssets($type)
    {
        if ($type !== 'css' && $type !== 'js' && $type !== 'trans') {
            throw new \RuntimeException('Asset type \'' . $type .
            '\' is unknown.');
        }

        // Add all assets to an asset manager first to avoid duplication
        //$assets = new LazyAssetManager($this->container->get('assetic.asset_factory'));
        $assets = array();

        $_assets = $this::listAssets();
        foreach($_assets[$type] as $asset) {
            $this->addAsset($assets, $type, $asset);
        }

        // Load all elements assets
        $translations = array();

        foreach ($this->getElements() as $region => $elements) {
            foreach ($elements as $element) {
                $element_assets = $element->getAssets();
                if (isset($element_assets[$type])) {
                    foreach ($element_assets[$type] as $asset) {
                        if ($type === 'trans') {
                            $elementTranslations = json_decode($this->container->get('templating')->render($asset),
                                true);
                            $translations = array_merge($translations,
                                $elementTranslations);
                        } else {
                            $this->addAsset($assets, $type,
                                $this->getReference($element, $asset));
                        }
                    }
                }
            }
        }

        $layerTranslations = array();
        // Load all layer assets
        foreach ($this->getLayersets() as $layerset) {
            foreach ($layerset->layerObjects as $layer) {
                $layer_assets = $layer->getAssets();
                if (isset($layer_assets[$type])) {
                    foreach ($layer_assets[$type] as $asset) {
                        if ($type === 'trans') {
                            if (!isset($layerTranslations[$asset])) {
                                $layerTranslations[$asset] = json_decode($this->container->get('templating')->render($asset),
                                    true);
                            }
                        } else {
                            $this->addAsset($assets, $type,
                                $this->getReference($layer, $asset));
                        }
                    }
                }
            }
        }
        foreach ($layerTranslations as $key => $value) {
            $translations = array_merge($translations, $value);
        }
        if ($type === 'trans') {
            $transAsset = new StringAsset('Mapbender.i18n = ' . json_encode($translations,
                    JSON_FORCE_OBJECT) . ';');
            $this->addAsset($assets, $type, $transAsset);
        }

        // Load the template assets last, so it can easily overwrite element
        // and layer assets for application specific styling for example
        foreach ($this->getTemplate()->getAssets($type) as $asset) {
            if ($type === 'trans') {
                $elementTranslations = json_decode($this->container->get('templating')->render($asset),
                    true);
                $translations = array_merge($translations, $elementTranslations);
            } else {
                $file = $this->getReference($this->template, $asset);
                $this->addAsset($assets, $type, $file);
            }
        }

        // Load extra assets given by application
        $extra_assets = $this->getEntity()->getExtraAssets();
        if (is_array($extra_assets) && array_key_exists($type, $extra_assets)) {
            foreach ($extra_assets[$type] as $asset) {
                $asset = trim($asset);
                $this->addAsset($assets, $type, $asset);
            }
        }

        return $assets;
    }

    private function addAsset(&$manager, $type, $reference)
    {
        $manager[] = $reference;
        return $manager;
    }

    /**
     * Get the configuration (application, elements, layers) as an StringAsset.
     * Filters can be applied later on with the ensureFilter method.
     *
     * @return StringAsset The configuration asset object
     */
    public function getConfiguration()
    {
        $configuration = array();

        $configuration['application'] = array(
            'title' => $this->entity->getTitle(),
            'urls' => $this->urls,
            'slug' => $this->getSlug());

        // Get all element configurations
        $configuration['elements'] = array();
        foreach ($this->getElements() as $region => $elements) {
            foreach ($elements as $element) {
                $configuration['elements'][$element->getId()] = array(
                    'init' => $element->getWidgetName(),
                    'configuration' => $element->getConfiguration());
            }
        }

        // Get all layer configurations
        $configuration['layersets'] = array();
        foreach ($this->getLayersets() as $layerset) {
            $configuration['layersets'][$layerset->getId()] = array();
            $num = 0;
            foreach ($layerset->layerObjects as $layer) {
                $layerconf = array(
                    $layer->getId() => array(
                        'type' => $layer->getType(),
                        'title' => $layer->getTitle(),
                        'configuration' => $layer->getConfiguration($this->container->get('signer'))));
                $configuration['layersets'][$layerset->getId()][$num] = $layerconf;
                $num++;
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
    public function getElement($id)
    {
        $elements = $this->getElements();
        foreach ($elements as $region => $element_list) {
            foreach ($element_list as $element) {
                if ($id == $element->getId()) {
                    return $element;
                }
            }
        }
        throw new NotFoundHttpException();
    }

    /**
     * Build an Assetic reference path from a given objects bundle name(space)
     * and the filename/path within that bundles Resources/public folder.
     *
     * @todo: This is duplicated in DumpMapbenderAssetsCommand
     *
     * @param object $object
     * @param string $file
     * @return string
     */
    private function getReference($object, $file)
    {
        // If it starts with an @ we assume it's already an assetic reference
        if ($file[0] !== '@') {
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
    public function getTemplate()
    {
        if ($this->template === null) {
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
    public function getElements($region = null)
    {
        if ($this->elements === null) {
            $securityContext = $this->container->get('security.context');
            $aclProvider = $this->container->get('security.acl.provider');
            // Set up all elements (by region)
            $this->elements = array();
            foreach ($this->entity->getElements() as $entity) {
                $application_entity = $this->getEntity();
                if ($application_entity::SOURCE_DB === $application_entity->getSource()){
                    try {
                        // If no ACL exists, an exception is thrown
                        $acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($entity));
                        // An empy ACL may exist, too
                        if(count($acl->getObjectAces()) > 0 && !$securityContext->isGranted('VIEW', $entity)){
                            continue;
                        }
                    } catch(\Exception $e) {}
                } else if ($application_entity::SOURCE_YAML === $application_entity->getSource()
                    && count($entity->yaml_roles)) {
                    $passed = false;
                    foreach ($entity->yaml_roles as $role) {
                        if ($securityContext->isGranted($role)) {
                            $passed = true;
                            break;
                        }
                    }
                    if (!$passed) {
                        continue;
                    }
                }
                $class = $entity->getClass();
                if (!$entity->getEnabled()) {
                    continue;
                }
                $element = new $class($this, $this->container, $entity);
                $r = $entity->getRegion();

                if (!array_key_exists($r, $this->elements)) {
                    $this->elements[$r] = array();
                }
                $this->elements[$r][] = $element;
            }

            // Sort each region element's by weight
            foreach ($this->elements as $r => $elements) {
                usort($elements,
                    function($a, $b) {
                        $wa = $a->getEntity()->getWeight();
                        $wb = $b->getEntity()->getWeight();
                        if ($wa == $wb) {
                            return 0;
                        }
                        return ($wa < $wb) ? -1 : 1;
                    });
            }
        }

        if ($region) {
            return array_key_exists($region, $this->elements) ?
                $this->elements[$region] : array();
        } else {
            return $this->elements;
        }
    }

    /**
     * Returns all layersets
     *
     * @return array the layersets
     */
    public function getLayersets()
    {
        if ($this->layers === null) {

            // Set up all elements (by region)
            $this->layers = array();
            foreach ($this->entity->getLayersets() as $layerset) {
                $layerset->layerObjects = array();
                foreach ($layerset->getInstances() as $instance) {
                    if ($this->getEntity()->getSource() === Entity::SOURCE_YAML) {
                        $layerset->layerObjects[] = $instance;
                    } else {
                        if ($instance->getEnabled()) {
                            $layerset->layerObjects[] = $instance;
                        }
                    }
                }
                $this->layers[$layerset->getId()] = $layerset;
            }
        }
        return $this->layers;
    }

}
