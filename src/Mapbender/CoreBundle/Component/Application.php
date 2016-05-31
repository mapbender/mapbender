<?php
namespace Mapbender\CoreBundle\Component;

use Assetic\Asset\StringAsset;
use Doctrine\ORM\PersistentCollection;
use Mapbender\CoreBundle\Entity\Application as Entity;
use Mapbender\CoreBundle\Entity\Layerset;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;

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
     * @var Entity
     */
    protected $entity;

    /**
     * @param ContainerInterface $container The container
     * @param Entity             $entity    The configuration entity
     * @param array              $urls      Array of runtime URLs
     */
    public function __construct(ContainerInterface $container, Entity $entity, array $urls)
    {
        $this->container = $container;
        $this->entity    = $entity;
        $this->urls      = $urls;
    }

    /*************************************************************************
     *                                                                       *
     *                    Configuration entity handling                      *
     *                                                                       *
     *************************************************************************/

    /**
     * Get the configuration entity.
     *
     * @return Entity $entity
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
     * @param string  $format Output format, defaults to HTML
     * @param boolean $html   Whether to render the HTML itself
     * @param boolean $css    Whether to include the CSS links
     * @param boolean $js     Whether to include the JavaScript
     * @param bool    $trans
     * @return string $html The rendered HTML
     */
    public function render($format = 'html', $html = true, $css = true, $js = true, $trans = true)
    {
        $template = $this->getTemplate();
        return $template->render($format, $html, $css, $js, $trans);
    }

    /**
     * Lists assets.
     * @return array
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/stubs.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.application.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.model.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.application.wdt.js',
            ),
            'css' => array(),
            'trans' => array('@MapbenderCoreBundle/Resources/public/mapbender.trans.js')
        );
    }

    /**
     * Get the assets as an AsseticCollection.
     * Filters can be applied later on with the ensureFilter method.
     *
     * @param string $type Can be 'css' or 'js' to indicate which assets to dump
     * @return array
     */
    public function getAssets($type)
    {
        if ($type !== 'css' && $type !== 'js' && $type !== 'trans') {
            throw new \RuntimeException('Asset type \'' . $type .
                '\' is unknown.');
        }

        // Add all assets to an asset manager first to avoid duplication
        //$assets = new LazyAssetManager($this->container->get('assetic.asset_factory'));
        $assets            = array();
        $translations      = array();
        $layerTranslations = array();
        $templating        = $this->container->get('templating');
        $_assets           = $this::listAssets();

        foreach ($_assets[$type] as $asset) {
            $this->addAsset($assets, $type, $asset);
        }

        // Load all elements assets
        foreach ($this->getTemplate()->getAssets($type) as $asset) {
            if ($type === 'trans') {
                $elementTranslations = json_decode($templating->render($asset), true);
                $translations = array_merge($translations, $elementTranslations);
            } else {
                $file = $this->getReference($this->template, $asset);
                $this->addAsset($assets, $type, $file);
            }
        }

        foreach ($this->getElements() as $region => $elements) {
            foreach ($elements as $element) {
                $element_assets = $element->getAssets();
                if (isset($element_assets[$type])) {
                    foreach ($element_assets[$type] as $asset) {
                        if ($type === 'trans') {
                            $elementTranslations =
                                json_decode($templating->render($asset), true);
                            $translations = array_merge($translations, $elementTranslations);
                        } else {
                            $this->addAsset($assets, $type, $this->getReference($element, $asset));
                        }
                    }
                }
            }
        }

        // Load all layer assets
        foreach ($this->getLayersets() as $layerset) {
            foreach ($layerset->layerObjects as $layer) {
                $layer_assets = $layer->getAssets();
                if (isset($layer_assets[$type])) {
                    foreach ($layer_assets[$type] as $asset) {
                        if ($type === 'trans') {
                            if (!isset($layerTranslations[$asset])) {
                                $layerTranslations[$asset] =
                                    json_decode($templating->render($asset), true);
                            }
                        } else {
                            $this->addAsset($assets, $type, $this->getReference($layer, $asset));
                        }
                    }
                }
            }
        }
        foreach ($layerTranslations as $key => $value) {
            $translations = array_merge($translations, $value);
        }
        if ($type === 'trans') {
            $transAsset = new StringAsset('Mapbender.i18n = ' . json_encode($translations, JSON_FORCE_OBJECT) . ';');
            $this->addAsset($assets, $type, $transAsset);
        }

        // Load the late template assets last, so it can easily overwrite element
        // and layer assets for application specific styling for example
        foreach ($this->getTemplate()->getLateAssets($type) as $asset) {
            if ($type === 'trans') {
                $elementTranslations = json_decode($templating->render($asset), true);
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

        if($type === 'js') {
            $app_loader = new StringAsset($templating->render(
                '@MapbenderCoreBundle/Resources/views/application.config.loader.js.twig',
                array('application' => $this)));
            $this->addAsset($assets, $type, $app_loader);
        }

        return $assets;
    }

    /**
     * @return StringAsset
     */
    public function getCustomCssAsset()
    {
        $entity = $this->getEntity();
        if ($entity->getCustomCss()) {
            return new StringAsset($entity->getCustomCss());
        }
    }

    /**
     * @param $manager
     * @param $type
     * @param $reference
     * @return array
     */
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
            'publicOptions' => $this->entity->getPublicOptions(),
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
        $configuration['layersetmap'] = array();
        foreach ($this->getLayersets() as $layerset) {
            $idStr = '' . $layerset->getId();
            $configuration['layersets'][$idStr] = array();
            $configuration['layersetmap'][$idStr] = $layerset->getTitle() ? $layerset->getTitle() : $idStr;
            $num = 0;
            foreach ($layerset->layerObjects as $layer) {
                $instHandler = EntityHandler::createHandler($this->container, $layer);
                $conf = $instHandler->getConfiguration($this->container->get('signer'));
                if ($conf) {
                    $configuration['layersets'][$idStr][$num] = array(
                        $layer->getId() => array(
                            'type' => strtolower($layer->getType()),
                            'title' => $layer->getTitle(),
                            'configuration' => $conf
                        )
                    );
                    $num++;
                }
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
        /** @var Element[] $elements */
        $regions = $this->getElements();
        $r       = null;
        foreach ($regions as $region => $elements) {
            foreach ($elements as $element) {
                if ($id == $element->getId()) {
                    $r = $element;
                    break;
                }
            }
        }
        return $r;
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
        $firstChar = $file[0];
        if ($firstChar == "/" ) {
            return "../../web/".substr($file,1);
        } elseif ($firstChar == "." ) {
            return $file;
        } elseif ($firstChar !== '@') {
            $namespaces = explode('\\', get_class($object));
            $bundle     = sprintf('%s%s', $namespaces[0], $namespaces[1]);
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
        if (!$this->template) {
            $template       = $this->entity->getTemplate();
            $this->template = new $template($this->container, $this);
        }
        return $this->template;
    }

    /**
     * Get region elements, optionally by region
     *
     * @param string $region Region to get elements for. If null, all elements  are returned.
     * @return PersistentCollection
     */
    public function getElements($region = null)
    {
        if ($this->elements === null) {
            $securityContext = $this->container->get('security.context');

            // preload acl in one single sql query
            $oids        = array();
            $aclProvider = $this->container->get('security.acl.provider');
            $acls        = null;
            foreach ($this->entity->getElements() as $entity) {
                $oids[] = ObjectIdentity::fromDomainObject($entity);
            }
            try {
                $aclProvider->findAcls($oids);
            } catch (NotAllAclsFoundException $e) {
                $acls = $e->getPartialResult();
            } catch (\Exception $e) {
                ;
            }

            // Set up all elements (by region)
            $this->elements = array();
            foreach ($this->entity->getElements() as $entity) {
                $application_entity = $this->getEntity();
                if ($application_entity->isDbBased()) {
                    try {
                        // If no ACL exists, an exception is thrown
                        $acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($entity));
                        // An empy ACL may exist, too
                        if (count($acl->getObjectAces()) > 0 && !$securityContext->isGranted('VIEW', $entity)) {
                            continue;
                        }
                    } catch (\Exception $e) {
                        ;
                    }
                } elseif ($application_entity->isYamlBased()
                    && count($entity->getYamlRoles())) {
                    $passed = false;
                    foreach ($entity->getYamlRoles() as $role) {
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
            $this->sortElementsByWidth();

        }

        if ($region) {
            return array_key_exists($region, $this->elements) ?
                $this->elements[$region] : array();
        } else {
            return $this->elements;
        }
    }

    /**
     * Returns all layer sets
     *
     * @return Layerset[] Layer sets
     */
    public function getLayersets()
    {
        if ($this->layers === null) {
            // Set up all elements (by region)
            $this->layers = array();
            foreach ($this->entity->getLayersets() as $layerSet) {
                $layerSet->layerObjects = array();
                foreach ($layerSet->getInstances() as $instance) {
                    if ($this->getEntity()->isYamlBased()) {
                        $layerSet->layerObjects[] = $instance;
                    } else {
                        if ($instance->getEnabled()) {
                            $layerSet->layerObjects[] = $instance;
                        }
                    }
                }
                $this->layers[ $layerSet->getId() ] = $layerSet;
            }
        }
        return $this->layers;
    }

    /**
     * Checks and generates a valid slug.
     *
     * @param ContainerInterface $container container
     * @param string             $slug      slug to check
     * @param string             $suffix
     * @return string a valid generated slug
     */
    public static function generateSlug($container, $slug, $suffix = 'copy')
    {
        $application = $container->get('mapbender')->getApplicationEntity($slug);
        if (!$application) {
            return $slug;
        } else {
            $count = 0;
        }
        $rep = $container->get('doctrine')->getRepository('MapbenderCoreBundle:Application');
        do {
            $copySlug = $slug . "_" . $suffix . ($count > 0 ? '_' . $count : '');
            $count++;
        } while ($rep->findOneBySlug($copySlug));
        return $copySlug;
    }

    /**
     * Returns the public "uploads" directory.
     *
     * @param ContainerInterface $container Container
     * @param bool               $webRelative
     * @return string the path to uploads dir or null.
     */
    public static function getUploadsDir($container, $webRelative = false)
    {
        $uploads_dir = $container->get('kernel')->getRootDir() . '/../web/'
            . $container->getParameter("mapbender.uploads_dir");
        $ok = true;
        if (!is_dir($uploads_dir)) {
            $ok = mkdir($uploads_dir);
        }
        if ($ok) {
            if (!$webRelative) {
                return $uploads_dir;
            } else {
                return $container->getParameter("mapbender.uploads_dir");
            }
        } else {
            return null;
        }
    }

    /**
     * Returns the application's public directory.
     *
     * @param ContainerInterface $container Container
     * @param string             $slug      application's slug
     * @return boolean true if the application's directories are created or exist otherwise false.
     */
    public static function getAppWebDir($container, $slug)
    {
        return Application::createAppWebDir($container, $slug)
            ? Application::getUploadsDir($container, $slug) . "/" . $slug
            : null;
    }

    /**
     * Creates or checks if the application's public directory is created or exist.
     *
     * @param ContainerInterface $container Container
     * @param string $slug application's slug
     * @param string $old_slug the old application's slug.
     * @return boolean true if the application's directories are created or
     * exist otherwise false.
     */
    public static function createAppWebDir($container, $slug, $old_slug = null)
    {
        $uploads_dir = Application::getUploadsDir($container);
        if ($uploads_dir === null) {
            return false;
        }
        if ($old_slug === null) {
            $slug_dir = $uploads_dir . "/" . $slug;
            if (!is_dir($slug_dir)) {
                return mkdir($slug_dir);
            } else {
                return true;
            }
        } else {
            $old_slug_dir = $uploads_dir . "/" . $old_slug;
            if (is_dir($old_slug_dir)) {
                $slug_dir = $uploads_dir . "/" . $slug;
                return rename($old_slug_dir, $slug_dir);
            } else {
                if (mkdir($old_slug_dir)) {
                    $slug_dir = $uploads_dir . "/" . $slug;
                    return rename($old_slug_dir, $slug_dir);
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Removes application's public directoriy.
     *
     * @param ContainerInterface $container Container
     * @param string $slug application's slug
     * @return boolean true if the directories are removed or not exist otherwise false
     */
    public static function removeAppWebDir($container, $slug)
    {
        $uploads_dir = Application::getUploadsDir($container);
        if (!is_dir($uploads_dir)) {
            return true;
        }
        $slug_dir = $uploads_dir . "/" . $slug;
        if (!is_dir($slug_dir)) {
            return true;
        } else {
            return Utils::deleteFileAndDir($slug_dir);
        }
    }

    /**
     * Returns an url to application's public directory.
     *
     * @param ContainerInterface $container Container
     * @param string $slug application's slug
     * @return string a url to wmc directory or to file with "$filename"
     */
    public static function getAppWebUrl($container, $slug)
    {
        return Application::getUploadsUrl($container) . "/" . $slug;
    }

    /**
     * Returns an url to public "uploads" directory.
     *
     * @param ContainerInterface $container Container
     * @return string an url to public "uploads" directory
     */
    public static function getUploadsUrl($container)
    {
        $base_url = Application::getBaseUrl($container);
        return $base_url . '/' . Application::getUploadsDir($container, true);
    }

    /**
     * Returns a base url.
     *
     * @param ContainerInterface $container Container
     * @return string a base url
     */
    public static function getBaseUrl($container)
    {
        $request = $container->get('request');
        return $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
    }

    /**
     * Copies an application web order.
     *
     * @param ContainerInterface $container Container
     * @param string $srcSslug source application slug
     * @param string $destSlug destination application slug
     * @return boolean true if the application  order has been copied otherwise false.
     */
    public static function copyAppWebDir($container, $srcSslug, $destSlug)
    {
        $src = Application::getAppWebDir($container, $srcSslug);
        $dst = Application::getAppWebDir($container, $destSlug);
        if ($src === null || $dst === null) {
            return false;
        }
        Utils::copyOrderRecursive($src, $dst);
        return true;
    }

    /**
     * Sort region elements by width
     */
    protected function sortElementsByWidth()
    {
        // Sort each region element's by weight
        /** @var Element[] $elements */
        foreach ($this->elements as $r => $elements) {
            usort(
                $elements,
                function (Element $a, Element $b) {
                    $wa = $a->getEntity()->getWeight();
                    $wb = $b->getEntity()->getWeight();
                    if ($wa == $wb) {
                        return 0;
                    }
                    return ($wa < $wb) ? -1 : 1;
                }
            );
        }
    }
}
