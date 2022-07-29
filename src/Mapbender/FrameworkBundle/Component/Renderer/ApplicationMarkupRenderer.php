<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;


use Mapbender\Component\Application\ElementDistribution;
use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\Filesystem\Exception\IOException;
use Twig;

class ApplicationMarkupRenderer
{
    /** @var Twig\Environment */
    protected $templatingEngine;
    /** @var ApplicationTemplateRegistry */
    protected $templateRegistry;
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var ElementMarkupRenderer */
    protected $elementRenderer;
    /** @var UploadsManager */
    protected $uploadsManager;
    /** @var bool */
    protected $allowResponsiveContainers;

    /** @var ElementDistribution[] */
    protected $distributions = array();

    public function __construct(Twig\Environment $templatingEngine,
                                ApplicationTemplateRegistry $templateRegistry,
                                ElementFilter $elementFilter,
                                ElementMarkupRenderer $elementRenderer,
                                UploadsManager $uploadsManager,
                                $allowResponsiveContainers)
    {
        $this->templatingEngine = $templatingEngine;
        $this->templateRegistry = $templateRegistry;
        $this->elementFilter = $elementFilter;
        $this->elementRenderer = $elementRenderer;
        $this->uploadsManager = $uploadsManager;
        $this->allowResponsiveContainers = $allowResponsiveContainers;
    }

    /**
     * @param Application $application
     * @return string
     */
    public function renderApplication(Application $application)
    {
        $templateObj = $this->templateRegistry->getApplicationTemplate($application);
        $twigTemplate = $templateObj->getTwigTemplate();
        $vars = array_replace($templateObj->getTemplateVars($application), array(
            'application' => $application,
            'uploads_dir' => $this->getPublicUploadsBaseUrl($application),
            'body_class' => $templateObj->getBodyClass($application),
        ));
        return $this->templatingEngine->render($twigTemplate, $vars);
    }

    /**
     * @param Application $application
     * @param $regionName
     * @param bool $suppressEmptyRegion
     * @return string
     */
    public function renderRegionByName(Application $application, $regionName, $suppressEmptyRegion = true)
    {
        $elementBucket = $this->getElementDistribution($application)->getRegionBucketByName($regionName);
        $elements = $elementBucket ? $elementBucket->getElements() : array();
        if ($elements || !$suppressEmptyRegion) {
            $template = $this->templateRegistry->getApplicationTemplate($application);
            $skin = $template->getRegionTemplate($application, $regionName);
            return $this->templatingEngine->render($skin, $this->getRegionTemplateVars($application, $regionName, $elements));
        } else {
            return '';
        }
    }

    public function renderFloatingElements(Application $application, $anchorValue)
    {
        $elements = $this->getElementDistribution($application)->getFloatingElements($anchorValue);
        if ($elements) {
            return $this->elementRenderer->renderFloatingElements($elements);
        } else {
            return '';
        }
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return string
     */
    public function renderRegionContentByName(Application $application, $regionName)
    {
        $elements = $this->getRegionElements($application, $regionName);
        if ($elements) {
            return $this->elementRenderer->renderElements($elements);
        } else {
            return '';
        }
    }

    /**
     * @param Application $application
     * @return string
     */
    public function renderMap(Application $application)
    {
        $mapElement = $this->getElementDistribution($application)->getMapElement();
        return $this->elementRenderer->renderElements(array($mapElement));
    }

    /**
     * @param Application $application
     * @return ElementDistribution
     */
    public function getElementDistribution(Application $application)
    {
        $hash = \spl_object_hash($application);
        if (empty($this->distributions[$hash])) {
            $this->distributions[$hash] = $this->createElementDistribution($application);
        }
        return $this->distributions[$hash];
    }

    /**
     * @param Application $application
     * @return ElementDistribution
     */
    public function createElementDistribution(Application $application)
    {
        $elements = $this->elementFilter->prepareFrontend($application->getElements(), true, true);
        return new ElementDistribution($elements);
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @param Element[] $elements
     * @return array
     */
    protected function getRegionTemplateVars(Application $application, $regionName, $elements)
    {
        $template = $this->templateRegistry->getApplicationTemplate($application);
        $props = $this->extractRegionProperties($application, $regionName);
        $props += $template->getRegionPropertiesDefaults($regionName);
        $classes = $template->getRegionClasses($application, $regionName);
        if ($this->allowResponsiveContainers && $application->getMapEngineCode() !== Application::MAP_ENGINE_OL2) {
            switch (ArrayUtil::getDefault($props, 'screenType')) {
                default:
                case ScreenTypes::ALL;
                    // nothing;
                    break;
                case ScreenTypes::DESKTOP_ONLY:
                    $classes[] = 'hide-screentype-mobile';
                    break;
                case ScreenTypes::MOBILE_ONLY:
                    $classes[] = 'hide-screentype-desktop';
                    break;
            }
        }
        // HACK: fix unit-less sidepane width (must be CSS unit)
        /** @todo: Template class should be responsible for a) defaults (currently in CSS only), b) fixing malformed values */
        if (!empty($props['width']) && \preg_match('#\d$#', $props['width'])) {
            $props['width'] = $props['width'] . 'px';
        }

        return array_replace($template->getRegionTemplateVars($application, $regionName), array(
            'elements' => $elements,
            'region_name' => $regionName,
            'application' => $application,
            'region_class' => implode(' ', $classes),
            'region_props' => $props,
        ));
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @return array
     */
    protected static function extractRegionProperties(Application $application, $regionName)
    {
        foreach ($application->getRegionProperties() ?: array() as $regionProps) {
            if ($regionProps->getName() === $regionName) {
                return $regionProps->getProperties() ?: array();
            }
        }
        return array();
    }

    public function renderToolbarInlineContent(Application $application, $regionName)
    {
        $elr = $this->elementRenderer;
        $elements = $this->getRegionElements($application, $regionName, function(Element $element) use ($elr) {
            return !$elr->isMenuSupported($element);
        });
        if ($elements) {
            return $this->elementRenderer->renderElements($elements);
        } else {
            return '';
        }
    }

    public function renderToolbarMenuContent(Application $application, $regionName)
    {
        $elr = $this->elementRenderer;
        $elements = $this->getRegionElements($application, $regionName, function(Element $element) use ($elr) {
            return $elr->isMenuSupported($element);
        });
        if ($elements) {
            return $this->elementRenderer->renderElements($elements);
        } else {
            return '';
        }
    }

    /**
     * @param Application $application
     * @param string $regionName
     * @param callable|null $filter
     * @return Element[]
     */
    protected function getRegionElements(Application $application, $regionName, $filter = null)
    {
        $elementBucket = $this->getElementDistribution($application)->getRegionBucketByName($regionName);
        $elements = $elementBucket ? $elementBucket->getElements() : array();
        if ($filter) {
            $elements = \array_filter($elements, $filter);
        }
        return $elements;
    }

    /**
     * @param Application $application
     * @return string|null
     */
    protected function getPublicUploadsBaseUrl(Application $application)
    {
        $slug = $application->getSlug();
        try {
            $this->uploadsManager->getSubdirectoryPath($slug, true);
            return $this->uploadsManager->getWebRelativeBasePath(false) . '/' . $slug;
        } catch (IOException $e) {
            return null;
        }
    }
}
