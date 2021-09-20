<?php


namespace Mapbender\CoreBundle\Extension;


use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\Exception\Application\MissingMapElementException;
use Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationContentExtension extends AbstractExtension
{

    /** @var ApplicationMarkupRenderer */
    protected $renderer;
    /** @var bool */
    protected $debug;

    /**
     * @param ApplicationMarkupRenderer $renderer
     * @param bool $debug
     */
    public function __construct(ApplicationMarkupRenderer $renderer,
                                $debug)
    {
        $this->renderer = $renderer;
        $this->debug = $debug;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mapbender_application_content';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return array(
            'region_markup' => new TwigFunction('region_markup', array($this, 'region_markup')),
            'region_content' => new TwigFunction('region_content', array($this, 'region_content')),
            'anchored_content_elements' => new TwigFunction('anchored_content_elements', array($this, 'anchored_content_elements')),
            'unanchored_content_elements' => new TwigFunction('unanchored_content_elements', array($this, 'unanchored_content_elements')),
            'map_markup' => new TwigFunction('map_markup', array($this, 'map_markup')),
            'toolbar_menu_content' => new TwigFunction('toolbar_menu_content', array($this, 'toolbar_menu_content')),
            'toolbar_inline_content' => new TwigFunction('toolbar_inline_content', array($this, 'toolbar_inline_content')),
        );
    }

    /**
     * @param Application $application
     * @return string
     */
    public function map_markup(Application $application)
    {
        try {
            return $this->renderer->renderMap($application);
        } catch (MissingMapElementException $e) {
            if ($this->debug) {
                throw $e;
            } else {
                return '';
            }
        }
    }

    /**
     * @param Application $application
     * @param $regionName
     * @param bool $suppressEmptyRegion
     * @return string
     */
    public function region_markup(Application $application, $regionName, $suppressEmptyRegion = true)
    {
        if (false !== strpos($regionName, 'content')) {
            throw new \LogicException("No support for 'content' region in region_markup");
        }
        return $this->renderer->renderRegionByName($application, $regionName, $suppressEmptyRegion);
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return string
     */
    public function region_content(Application $application, $regionName)
    {
        return $this->renderer->renderRegionContentByName($application, $regionName);
    }

    /**
     * @param Application $application
     * @param string|null $anchorValue empty for everything in sequence, or one of "top-left", "top-right", "bottom-left", "bottom-right"
     * @return string
     */
    public function anchored_content_elements(Application $application, $anchorValue = null)
    {
        if (!$anchorValue) {
            $validAnchors = Template::getValidOverlayAnchors();
            $parts = array();
            foreach ($validAnchors as $anchorValue) {
                $parts[] = $this->renderer->renderFloatingElements($application, $anchorValue);
            }
            return implode('', $parts);
        } else {
            return $this->renderer->renderFloatingElements($application, $anchorValue);
        }
    }

    /**
     * @param Application $application
     * @return string
     */
    public function unanchored_content_elements(Application $application)
    {
        return
            '<div class="hidden">'
            . $this->renderer->renderRegionContentByName($application, 'content')
            . '</div>'
        ;
    }

    public function toolbar_menu_content(Application $application, $regionName)
    {
        return $this->renderer->renderToolbarMenuContent($application, $regionName);
    }

    public function toolbar_inline_content(Application $application, $regionName)
    {
        return $this->renderer->renderToolbarInlineContent($application, $regionName);
    }
}
