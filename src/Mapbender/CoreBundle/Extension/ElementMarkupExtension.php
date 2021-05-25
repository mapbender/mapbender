<?php


namespace Mapbender\CoreBundle\Extension;

use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\Exception\Application\MissingMapElementException;
use Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupRenderer;
use Mapbender\FrameworkBundle\Component\Renderer\ElementMarkupRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ElementMarkupExtension extends AbstractExtension
{
    /** @var ElementMarkupRenderer */
    protected $markupRenderer;
    /** @var ApplicationMarkupRenderer */
    protected $applicationRenderer;

    /** @var bool */
    protected $debug;
    /** @var bool */
    protected $allowResponsiveElements;

    /**
     * @param ElementMarkupRenderer $markupRenderer
     * @param ApplicationMarkupRenderer $applicationRenderer
     * @param bool $allowResponsiveElements
     * @param bool $debug
     */
    public function __construct(ElementMarkupRenderer $markupRenderer,
                                ApplicationMarkupRenderer $applicationRenderer,
                                $allowResponsiveElements,
                                $debug)
    {
        $this->markupRenderer = $markupRenderer;
        $this->applicationRenderer = $applicationRenderer;
        $this->allowResponsiveElements = $allowResponsiveElements;
        $this->debug = $debug;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mapbender_element_markup';
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
            'element_visibility_class' => new TwigFunction('element_visibility_class', array($this, 'element_visibility_class')),
            'element_markup' => new TwigFunction('element_markup', array($this, 'element_markup')),
        );
    }

    /**
     * @param Application $application
     * @return string
     */
    public function map_markup(Application $application)
    {
        try {
            return $this->applicationRenderer->renderMap($application);
        } catch (MissingMapElementException $e) {
            if ($this->debug) {
                throw $e;
            } else {
                return '';
            }
        }
    }

    /**
     * @param Entity\Element|Component\Element $element
     * @return string
     */
    public function element_markup($element)
    {
        return $this->markupRenderer->renderElements(array($this->normalizeElementEntityArgument($element)));
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
        return $this->applicationRenderer->renderRegionByName($application, $regionName, $suppressEmptyRegion);
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return string
     */
    public function region_content(Application $application, $regionName)
    {
        return $this->applicationRenderer->renderRegionContentByName($application, $regionName);
    }

    /**
     * @param Application $application
     * @return string
     */
    public function unanchored_content_elements(Application $application)
    {
        $elementBucket = $this->applicationRenderer->getElementDistribution($application)->getRegionBucketByName('content');
        if ($elementBucket) {
            return $this->markupRenderer->renderElements($elementBucket->getElements());
        } else {
            return '';
        }
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
                $parts[] = $this->applicationRenderer->renderFloatingElements($application, $anchorValue);
            }
            return implode('', $parts);
        } else {
            return $this->applicationRenderer->renderFloatingElements($application, $anchorValue);
        }
    }

    /**
     * @param Entity\Element|Component\Element $element
     * @return string|null
     */
    public function element_visibility_class($element)
    {
        return $this->getElementVisibilityClass($this->normalizeElementEntityArgument($element));
    }

    /**
     * @param Entity\Element $element
     * @return string[]|null
     */
    protected function getElementWrapper(Entity\Element $element)
    {
        $visibilityClass = $this->getElementVisibilityClass($element);
        if ($visibilityClass) {
            return array(
                'tagName' => 'div',
                'class' => $visibilityClass,
            );
        } else {
            return null;
        }
    }

    /**
     * @param Entity\Element $element
     * @return string|null
     */
    protected function getElementVisibilityClass(Entity\Element $element)
    {
        // Allow screenType filtering only on current map engine
        if (!$this->allowResponsiveElements || $element->getApplication()->getMapEngineCode() === Application::MAP_ENGINE_OL2) {
            return null;
        }
        switch ($element->getScreenType()) {
            case ScreenTypes::ALL:
            default:
                return null;
            case ScreenTypes::MOBILE_ONLY:
                return 'hide-screentype-desktop';
            case ScreenTypes::DESKTOP_ONLY:
                return 'hide-screentype-mobile';
        }
    }

    /**
     * @param Entity\Element|Component\Element $element
     * @return Entity\Element
     * @throws \InvalidArgumentException
     */
    protected function normalizeElementEntityArgument($element)
    {
        if ($element instanceof Component\Element) {
            $element = $element->getEntity();
        }
        if (!$element instanceof Entity\Element) {
            throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? \get_class($element) : gettype($element));
        }
        return $element;
    }
}
