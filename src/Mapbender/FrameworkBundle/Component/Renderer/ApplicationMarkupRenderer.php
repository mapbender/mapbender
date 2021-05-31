<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;


use Mapbender\Component\Application\ElementDistribution;
use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class ApplicationMarkupRenderer
{
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var ElementMarkupRenderer */
    protected $elementRenderer;
    /** @var EngineInterface */
    protected $templatingEngine;
    /** @var bool */
    protected $allowResponsiveContainers;

    /** @var ElementDistribution[] */
    protected $distributions = array();

    public function __construct(ElementFilter $elementFilter,
                                ElementMarkupRenderer $elementRenderer,
                                EngineInterface $templatingEngine,
                                $allowResponsiveContainers)
    {
        $this->elementFilter = $elementFilter;
        $this->elementRenderer = $elementRenderer;
        $this->templatingEngine = $templatingEngine;
        $this->allowResponsiveContainers = $allowResponsiveContainers;
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
            $template = $this->getTemplateDescriptor($application);
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
        $elementBucket = $this->getElementDistribution($application)->getRegionBucketByName($regionName);
        $elements = $elementBucket ? $elementBucket->getElements() : array();
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
        $elements = $this->elementFilter->prepareFrontend($application->getElements(), true);
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
        $template = $this->getTemplateDescriptor($application);
        $props = $this->extractRegionProperties($application, $regionName);
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
     * @return Template
     */
    protected static function getTemplateDescriptor(Application $application)
    {
        /** @var string|Template $templateCls */
        $templateCls = $application->getTemplate();
        /** @var Template $templateObj */
        $templateObj = new $templateCls();
        if (!($templateObj instanceof Template)) {
            throw new \LogicException("Invalid template class " . get_class($templateObj));
        }
        return $templateObj;
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
}
