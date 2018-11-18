<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all application templates.
 *
 * @author Christian Wygoda
 */
abstract class Template implements IApplicationTemplateInterface
{
    protected $container;

    /** @var Application */
    protected $application;


    /**
     * Template constructor.
     *
     * @param ContainerInterface $container
     * @param Application        $application
     */
    public function __construct(ContainerInterface $container, Application $application)
    {
        $this->container    = $container;
        $this->application  = $application;
    }

    /**
     * @return array
     */
    static public function listAssets()
    {
        return array();
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
     * @param string $type Asset type to list, can be 'css' or 'js'
     * @return array
     */
    public function getLateAssets($type)
    {
        switch ($type) {
            case 'js':
            case 'css':
            case 'trans':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported late asset type " . print_r($type, true));
        }
    }

    /**
     * Render the application
     *
     * @param string $format Output format, defaults to HTML
     * @param boolean $html Whether to render the HTML itself
     * @param boolean $css  Whether to include the CSS links
     * @param boolean $js   Whether to include the JavaScript
     * @return string $html The rendered HTML
     */
    public function render($format = 'html', $html = true, $css = true, $js = true)
    {
        $application       = $this->application;
        $applicationEntity = $application->getEntity();
        $templateRender    = $this->container->get('templating');
        $uploadsDir = Application::getAppWebDir($this->container, $this->application->getSlug());

        return $templateRender->render($this->getTwigTemplate(), array(
                'html'                 => $html,
                'css'                  => $css,
                'js'                   => $js,
                'application'          => $application,
                'region_props'         => $applicationEntity->getNamedRegionProperties(),
                'default_region_props' => static::getRegionsProperties(),
                'uploads_dir' => $uploadsDir,
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
        return array();
    }

    /**
     * @return string TWIG template path
     */
    abstract public function getTwigTemplate();
}

