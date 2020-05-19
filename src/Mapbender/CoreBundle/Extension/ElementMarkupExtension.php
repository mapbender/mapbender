<?php


namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Component;
use Mapbender\CoreBundle\Element\Map;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ElementMarkupExtension extends AbstractExtension
{
    /** @var Component\Presenter\ApplicationService */
    protected $appService;
    /** @var TwigEngine */
    protected $templatingEngine;
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
     */
    public function __construct(Component\Presenter\ApplicationService $appService, $templatingEngine)
    {
        $this->appService = $appService;
        $this->templatingEngine = $templatingEngine;
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
        return $this->renderComponents(array($this->mapElement));
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
            return $this->renderComponents($this->nonContentRegionMap[$regionName], $regionName);
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
     * @param string $anchorValue one of "top-left", "top-right", "bottom-left", "bottom-right"
     * @return string
     */
    public function anchored_content_elements($application, $anchorValue)
    {
        $this->updateBuffers($application);
        if (!empty($this->anchoredContentElements[$anchorValue])) {
            $glue = '<div class="element-wrapper">';
            return $this->renderComponents($this->anchoredContentElements[$anchorValue], null, $glue);
        } else {
            return '';
        }
    }

    /**
     * @param Component\Element[] $components
     * @param string|null $regionName
     * @param string|null $glue HTML opening tag
     * @return string
     */
    protected function renderComponents($components, $regionName = null, $glue = null)
    {
        if ($regionName) {
            if ($glue) {
                throw new \LogicException("Can't evaluate glue when combined with explicit region name");
            }
            $skin = '@MapbenderCore/Template/region.html.twig';
            $vars = array(
                'application' => array(
                    'elements' => array(
                        $regionName => $components,
                    ),
                ),
                'region_props' => $this->regionProperties,
                'region' => $regionName,
            );
            return $this->templatingEngine->render($skin, $vars);
        }
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
        if ($application instanceof Component\Application) {
            $application = $application->getEntity();
        }
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
                    // @todo: use a more specific ~configuration error exception
                    throw new \RuntimeException("Invalid application: multiple map elements");
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
        if (!$this->mapElement) {
            // @todo: use a more specific ~configuration error exception
            throw new \RuntimeException("Invalid application: missing map element");
        }
    }
}
