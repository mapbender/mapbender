<?php


namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Component;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Element\Map;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\Exception\Application\MissingMapElementException;
use Mapbender\Exception\Application\MultipleMapElementsException;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ElementMarkupExtension extends AbstractExtension
{
    /** @var Component\Presenter\ApplicationService */
    protected $appService;
    /** @var TwigEngine */
    protected $templatingEngine;
    /** @var bool */
    protected $debug;
    /** @var string */
    protected $bufferedHash;
    /** @var Map */
    protected $mapElement;
    /** @var Component\Element[][] */
    protected $anchoredContentElements;
    /** @var Component\Element[] */
    protected $unanchoredContentElements;
    /** @var Component\Element[][] */
    protected $nonContentRegionMap;
    /** @var array */
    protected $regionProperties;

    /**
     * @param Component\Presenter\ApplicationService $appService
     * @param TwigEngine $templatingEngine
     * @param bool $debug
     */
    public function __construct(Component\Presenter\ApplicationService $appService,
                                $templatingEngine,
                                $debug)
    {
        $this->appService = $appService;
        $this->templatingEngine = $templatingEngine;
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
        );
    }

    /**
     * @param Component\Application|Application $application
     * @return string
     */
    public function map_markup($application)
    {
        $this->updateBuffers($application);
        if (!$this->mapElement) {
            if ($this->debug) {
                throw new MissingMapElementException("Invalid application: missing map element");
            } else {
                return '';
            }
        }

        return $this->renderComponents(array($this->mapElement));
    }

    /**
     * @param Component\Application|Application $application
     * @param $regionName
     * @param bool $suppressEmptyRegion
     * @return string
     */
    public function region_markup($application, $regionName, $suppressEmptyRegion = true)
    {
        if (false !== strpos($regionName, 'content')) {
            throw new \LogicException("No support for 'content' region in region_markup");
        }
        $this->updateBuffers($application);
        if (!empty($this->nonContentRegionMap[$regionName])) {
            $elements = $this->nonContentRegionMap[$regionName];
        } else {
            $elements = array();
        }
        if ($elements || !$suppressEmptyRegion) {
            $application = $this->normalizeApplication($application);
            $template = $this->getTemplateDescriptor($application);
            $skin = $template->getRegionTemplate($application, $regionName);
            $vars = array_replace($template->getRegionTemplateVars($application, $regionName), array(
                'elements' => $elements,
                'region_name' => $regionName,
                'application' => $application,
            ));
            return $this->templatingEngine->render($skin, $vars);
        } else {
            return '';
        }
    }

    /**
     * @param Component\Application|Application $application
     * @param string $regionName
     * @return string
     */
    public function region_content($application, $regionName)
    {
        $this->updateBuffers($application);
        if ($regionName === 'content') {
            return $this->unanchored_content_elements($application);
        } elseif (!empty($this->nonContentRegionMap[$regionName])) {
            $glue = $this->getGlueTag($regionName);
            return $this->renderComponents($this->nonContentRegionMap[$regionName], $glue);
        } else {
            return '';
        }
    }

    /**
     * @param Component\Application|Application $application
     * @return string
     */
    public function unanchored_content_elements($application)
    {
        $this->updateBuffers($application);
        return $this->renderComponents($this->unanchoredContentElements);
    }

    /**
     * @param Component\Application|Application $application
     * @param string|null $anchorValue empty for everything in sequence, or one of "top-left", "top-right", "bottom-left", "bottom-right"
     * @return string
     */
    public function anchored_content_elements($application, $anchorValue = null)
    {
        $this->updateBuffers($application);
        if (!$anchorValue) {
            $validAnchors = Template::getValidOverlayAnchors();
            $parts = array();
            foreach ($validAnchors as $anchorValue) {
                $parts[] = $this->anchored_content_elements($application, $anchorValue);
            }
            return implode('', $parts);
        }
        if (!empty($this->anchoredContentElements[$anchorValue])) {
            $glue = '<div class="element-wrapper">';
            return $this->renderComponents($this->anchoredContentElements[$anchorValue], $glue);
        } else {
            return '';
        }
    }

    /**
     * @param Component\Element[] $components
     * @param string|null $glue HTML opening tag
     * @return string
     */
    protected function renderComponents($components, $glue = null)
    {
        $glueParts = array('', '');
        if ($glue) {
            $pattern = '#^<(\w+)[^/>]*>$#i';
            $matches = array();
            preg_match($pattern, $glue, $matches);
            if (!$matches) {
                throw new \RuntimeException("Invalid glue " . print_r($glue, true));
            }
            $glueParts = array($glue, "</{$matches[1]}>");
        }

        $markupFragments = array();
        foreach ($components as $component) {
            $markupFragments[] = $glueParts[0];
            $markupFragments[] = $component->render();
            $markupFragments[] = $glueParts[1];
        }
        return implode('', $markupFragments);
    }

    /**
     * @param Component\Application|Application $application
     */
    protected function updateBuffers($application)
    {
        $application = $this->normalizeApplication($application);
        $hash = spl_object_hash($application);
        if ($this->bufferedHash !== $hash) {
            $this->initializeBuffers($application);
            $this->bufferedHash = $hash;
        }
    }

    /**
     * @param Application $application
     */
    protected function initializeBuffers(Application $application)
    {
        $granted = $this->appService->getActiveElements($application);
        $this->mapElement = null;
        $this->nonContentRegionMap = array();
        $this->anchoredContentElements = array();
        $this->unanchoredContentElements = array();
        foreach ($granted as $elementComponent) {
            $elementEntity = $elementComponent->getEntity();
            $region = $elementEntity->getRegion();
            if ($elementComponent instanceof Map) {
                if ($this->mapElement) {
                    throw new MultipleMapElementsException("Invalid application: multiple map elements");
                }
                $this->mapElement = $elementComponent;
            } elseif ($region !== 'content') {
                if (!isset($this->nonContentRegionMap[$region])) {
                    $this->nonContentRegionMap[$region] = array();
                }
                $this->nonContentRegionMap[$region][] = $elementComponent;
            } else {
                // @todo: migrate config? already done?
                $config = $elementEntity->getConfiguration();
                if (!empty($config['anchor'])) {
                    $anchor = $config['anchor'];
                    if (!isset($this->anchoredContentElements[$anchor])) {
                        $this->anchoredContentElements[$anchor] = array();
                    }
                    $this->anchoredContentElements[$anchor][] = $elementComponent;
                } else {
                    $this->unanchoredContentElements[] = $elementComponent;
                }
            }
        }
        $this->regionProperties = $application->getNamedRegionProperties();
    }

    /**
     * Extract and return Application entity when passed an Application component, or pass
     * Application entity through directly.
     *
     * @param Component\Application|Application $application
     * @return Application
     * @throws \LogicException if argument is neither Application component nor Application entity
     */
    protected static function normalizeApplication($application)
    {
        if ($application instanceof Component\Application) {
            return $application->getEntity();
        } elseif (!$application || !($application instanceof Application)) {
            $type = ($application && \is_object($application)) ? get_class($application) : gettype($application);
            throw new \LogicException("Bad type {$type} passed as Application");
        } else {
            return $application;
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

    /**
     * Detect appropriate Element markup wrapping tag for a named region.
     *
     * @param string $regionName
     * @return string|null
     */
    protected static function getGlueTag($regionName)
    {
        switch (static::normalizeRegionName($regionName)) {
            case 'footer':
            case 'toolbar':
                return '<li class="toolBarItem">';
            case 'sidepane':
                // @todo: unify this
                return null;
            default:
                return null;
        }
    }

    /**
     * @param Application|Component\Application $application
     * @return Template
     */
    protected static function getTemplateDescriptor($application)
    {
        $application = static::normalizeApplication($application);
        /** @var string|Template $templateCls */
        $templateCls = $application->getTemplate();
        /** @var Template $templateObj */
        $templateObj = new $templateCls();
        if (!($templateObj instanceof Template)) {
            throw new \LogicException("Invalid template class " . get_class($templateObj));
        }
        return $templateObj;
    }
}
