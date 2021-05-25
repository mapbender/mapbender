<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;


use Mapbender\Component\Application\ElementDistribution;
use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationMarkupRenderer
{
    /** @var ElementMarkupRenderer */
    protected $elementRenderer;
    /** @var ElementFactory */
    protected $elementFactory;
    /** @var AuthorizationCheckerInterface  */
    protected $authorizationChecker;
    /** @var EngineInterface */
    protected $templatingEngine;
    /** @var bool */
    protected $allowResponsiveContainers;

    /** @var ElementDistribution[] */
    protected $distributions = array();

    public function __construct(ElementMarkupRenderer $elementRenderer,
                                ElementFactory $elementFactory,
                                EngineInterface $templatingEngine,
                                AuthorizationCheckerInterface $authorizationChecker,
                                $allowResponsiveContainers)
    {
        $this->elementRenderer = $elementRenderer;
        $this->elementFactory = $elementFactory;
        $this->templatingEngine = $templatingEngine;
        $this->authorizationChecker = $authorizationChecker;
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
            return $this->elementRenderer->renderElements($elements, array(
                'tagName' => 'div',
                'class' => 'element-wrapper',
            ));
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
            $glue = $this->getRegionGlue($regionName);
            return $this->elementRenderer->renderElements($elements, $glue);
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
        return new ElementDistribution($this->prepareDisplayableElements($application));
    }

    /**
     * @param Application $application
     * @return Element[]
     */
    protected function prepareDisplayableElements(Application $application)
    {
        $entitiesOut = array();
        foreach ($application->getElements() as $element) {
            $this->elementFactory->migrateElementConfiguration($element, true);
            $enabled = !$this->elementFactory->isTypeOfElementDisabled($element) && $element->getEnabled();
            if ($enabled && $this->authorizationChecker->isGranted('VIEW', $element)) {
                if (!$element->getTitle()) {
                    $element->setTitle($this->elementFactory->getDefaultTitle($element));
                }
                $entitiesOut[] = $element;
            }
        }
        return $entitiesOut;
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

    /**
     * Detect appropriate Element markup wrapping tag for a named region.
     *
     * @param string $regionName
     * @return string[]|null
     */
    protected static function getRegionGlue($regionName)
    {
        switch (static::normalizeRegionName($regionName)) {
            case 'footer':
            case 'toolbar':
                return array(
                    'tagName' => 'li',
                    'class' => 'toolBarItem',
                );
            case 'sidepane':
                // @todo: unify this
                return null;
            default:
                return null;
        }
    }

    /**
     * @param string $regionName
     * @return string
     */
    protected static function normalizeRegionName($regionName)
    {
        // Legacy lenience in patterns: allow postfixes / prefixes around region names, e.g.
        // "some-custom-project-footer"
        if (false !== strpos($regionName, 'footer')) {
            return 'footer';
        } elseif (false !== strpos($regionName, 'toolbar')) {
            return 'toolbar';
        } elseif (false !== strpos($regionName, 'sidepane')) {
            return 'sidepane';
        } elseif (false !== strpos($regionName, 'content')) {
            return 'content';
        } else {
            // fingers crossed
            return $regionName;
        }
    }
}
