<?php
namespace Mapbender\CoreBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all application templates.
 *
 * @author Christian Wygoda
 * @author Andriy Oblivantsev
 */
abstract class Template
{
    /** @var ContainerInterface */
    protected $container;

    /** @var Application */
    protected $application;

    /** @var string Bundle public resource path */
    protected static $resourcePath;

    /** @var string Application title */
    protected static $title;

    /** @var string Application TWIG template path */
    protected $twigTemplate;

    /** @var array Late assets */
    protected $lateAssets = array(
        'js'    => array(),
        'css'   => array(),
        'trans' => array(),
    );

    /** @var array Region properties */
    protected static $regionsProperties = array();

    /**  @var array Region names */
    protected static $regions = array();

    protected static $css          = array();
    protected static $js           = array();
    protected static $translations = array();

    /**
     * Template constructor.
     *
     * @param ContainerInterface $container
     * @param Application        $application
     */
    public function __construct(ContainerInterface $container, Application $application)
    {
        static::$resourcePath = '@' . $this->getBundleName() . '/Resources/public';
        $this->container    = $container;
        $this->application  = $application;
    }

    /**
     * Get the template title.
     *
     * This title is mainly used in the backend manager when creating a new
     * application.
     *
     * @return string
     */
    static public function getTitle()
    {
        return static::$title;
    }

    /**
     * @return array
     */
    static public function listAssets()
    {
        return array(
            'css'   => static::$css,
            'js'    => static::$js,
            'trans' => static::$translations
        );
    }

    /**
     * Get the element assets.
     *
     * Returns an array of references to asset files of the given type.
     * References can either be filenames/path which are searched for in the
     * Resources/public directory of the element's bundle or assetic references
     * indicating the bundle to search in:
     *
     * array(
     *   'foo.css'),
     *   '@MapbenderCoreBundle/Resources/public/foo.css'));
     *
     * @param string $type Asset type to list, can be 'css' or 'js'
     * @return array
     */
    public function getAssets($type)
    {
        $allAssets = $this::listAssets();
        $ownAssets = array_key_exists($type, $allAssets) ? $allAssets[$type] : array();
        $parent = get_parent_class($this);
        // Merge up all JavaScript assets, so we get all the required libraries into all
        // extending templates.
        if ($type === 'js' && $parent) {
            $allBaseAssets = call_user_func(array($parent, 'listAssets'), $type);
            $baseAssets = $ownAssets = array_key_exists($type, $allBaseAssets) ? $allBaseAssets[$type] : array();
            return array_unique(array_merge($baseAssets, $ownAssets));
        } else {
            return $ownAssets;
        }
    }

    /**
     * Get assets for late including. These will be appended to the asset output last.
     * Remember to list them in listAssets!
     *
     * @param string $type Asset type to list, can be 'css' or 'js'
     * @return array
     */
    public function getLateAssets($type)
    {
        return $this->lateAssets[ $type ];
    }

    /**
     * Get the template regions available in the Twig template.
     *
     * @return array
     */
    static public function getRegions()
    {
        return static::$regions;
    }

    /**
     * Render the application
     *
     * @param string  $format Output format, defaults to HTML
     * @param boolean $html   Whether to render the HTML itself
     * @param boolean $css    Whether to include the CSS links
     * @param boolean $js     Whether to include the JavaScript
     * @return string $html The rendered HTML
     */
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        $application       = $this->application;
        $applicationEntity = $application->getEntity();
        $templateRender    = $this->container->get('templating');

        return $templateRender->render($this->getTwigTemplate(), array(
                'html'                 => $html,
                'css'                  => $css,
                'js'                   => $js,
                'application'          => $application,
                'region_props'         => $applicationEntity->getNamedRegionProperties(),
                'default_region_props' => static::getRegionsProperties()
            )
        );
    }

    /**
     * Get the available regions properties.
     *
     * @return array
     */
    public static function getRegionsProperties()
    {
        return static::$regionsProperties;
    }

    /**
     * Get template bundle name
     *
     * @return string Bundle name
     */
    public function getBundleName()
    {
        $reflection = new \ReflectionClass(get_class($this));
        return preg_replace('/\\\\|Template$/', '', $reflection->getNamespaceName());
    }

    /**
     * Get resource path
     *
     * @return string
     */
    public static function getResourcePath()
    {
        return static::$resourcePath;
    }

    /**
     * @param Application $twigTemplate
     */
    public function setTwigTemplate($twigTemplate)
    {
        $this->twigTemplate = $twigTemplate;
    }

    /**
     * @param $title string Title
     */
    public static function setTitle($title)
    {
        static::$title = $title;
    }

    /**
     * @return string TWIG template path
     */
    public function getTwigTemplate()
    {
        return $this->twigTemplate;
    }

    /**
     * Add and merge unique assets
     *
     * @param       $type
     * @param array $assets
     */
    public static function addAndMergeUniqueAssets($type, array $assets)
    {
        static::${$type} = array_values(array_unique(array_merge(static::${$type}, $assets)));
    }
}

