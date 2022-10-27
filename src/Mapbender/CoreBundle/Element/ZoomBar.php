<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatingElement;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Utils\ApplicationUtil;

/**
 * The Zoombar element provides a control to pan and zoom, similar to the
 * OpenLayers PanZoomBar control. This element though is easier to use when
 * custom styling is needed.
 *
 * @author Christian Wygoda
 */
class ZoomBar extends AbstractElementService implements FloatingElement
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.zoombar.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.zoombar.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.zoombar.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/zoombar.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'components' => array(
                "rotation",
                "zoom_max",
                'zoom_home',
                "zoom_in_out",
                "zoom_slider",
            ),
            'anchor' => 'left-top',
            'draggable' => true,
            'zoomHomeRestoresLayers' => false,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbZoomBar';
    }

    public function getView(Element $element)
    {
        $mapElement = ApplicationUtil::getMapElement($element->getApplication());
        if (!$mapElement) {
            return false;
        }
        $view = new TemplateView('MapbenderCoreBundle:Element:zoombar.html.twig');
        $view->attributes['class'] = 'mb-element-zoombar';
        $scales = array();
        $mapConfig = $mapElement->getConfiguration();
        if (!empty($mapConfig['scales'])) {
            $scales = $mapConfig['scales'];
            asort($scales, SORT_NUMERIC | SORT_REGULAR);
        }
        $withDefaults = $element->getConfiguration() + $this->getDefaultConfiguration();
        $view->variables = array(
            'zoom_levels' => $scales,
            'configuration' => array_replace($withDefaults, array(
                'components' => $this->filterComponentList($element, $withDefaults['components']),
            )),
        );
        return $view;
    }

    /**
     * @param Element $entity
     * @param string[] $componentList
     * @return string[]
     */
    protected static function filterComponentList(Element $entity, $componentList)
    {
        if (in_array('zoom_slider', $componentList) && !in_array('zoom_in_out', $componentList)) {
            $componentList[] = 'zoom_in_out';
        }
        $componentList = array_values(array_diff($componentList, static::getComponentBlacklist($entity)));
        return $componentList;
    }

    protected static function getComponentBlacklist(Element $element)
    {
        $blackList = array();
        $application = $element->getApplication();
        if ($application) {
            switch ($application->getMapEngineCode()) {
                case Application::MAP_ENGINE_OL2:
                    $blackList[] = 'rotation';
                    break;
                default:
                    break;
            }
        }
        return $blackList;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ZoomBarAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:zoombar.html.twig';
    }
}
