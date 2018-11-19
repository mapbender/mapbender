<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateAssetDependencyInterface;
use Mapbender\CoreBundle\Component\Application\Template\IApplicationTemplateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines twig template and asset dependencies and regions for an Application template.
 * Also defines the displayable title of the template that is displayed in the backend when choosing or
 * displaying the template assigned to an Application.
 *
 * @author Christian Wygoda
 */
abstract class Template implements IApplicationTemplateInterface, IApplicationTemplateAssetDependencyInterface
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
     * {@inheritdoc}
     */
    public function getAssets($type)
    {
        switch ($type) {
            case 'js':
            case 'css':
            case 'trans':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    /**
     * {@inheritdoc}
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

