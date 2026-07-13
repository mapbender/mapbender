<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\ZoomBarAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatingElement;
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
    public static function getClassTitle(): string
    {
        return "mb.core.zoombar.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.zoombar.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbZoombar.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/zoombar.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'components' => [
                "rotation",
                "zoom_max",
                'zoom_home',
                "zoom_in_out",
                "zoom_slider",
            ],
            'anchor' => 'left-top',
            'draggable' => true,
            'zoomHomeRestoresLayers' => false,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbZoombar';
    }

    public function getView(Element $element): false|TemplateView
    {
        $mapElement = ApplicationUtil::getMapElement($element->getApplication());
        if (!$mapElement) {
            return false;
        }
        $view = new TemplateView('@MapbenderCore/Element/zoombar.html.twig');
        $view->attributes['class'] = 'mb-element-zoombar';
        $scales = [];
        $mapConfig = $mapElement->getConfiguration();
        if (!empty($mapConfig['scales'])) {
            $scales = $mapConfig['scales'];
            asort($scales, SORT_NUMERIC | SORT_REGULAR);
        }
        $withDefaults = $element->getConfiguration() + static::getDefaultConfiguration();
        $view->variables = [
            'zoom_levels' => $scales,
            'configuration' => array_replace($withDefaults, [
                'components' => static::filterComponentList($element, $withDefaults['components']),
            ]),
        ];
        return $view;
    }

    /**
     * @param Element $entity
     * @param string[] $componentList
     * @return string[]
     */
    protected static function filterComponentList(Element $entity, $componentList): array
    {
        if (in_array('zoom_slider', $componentList) && !in_array('zoom_in_out', $componentList)) {
            $componentList[] = 'zoom_in_out';
        }
        $componentList = array_values(array_diff($componentList, static::getComponentBlacklist($entity)));
        return $componentList;
    }

    protected static function getComponentBlacklist(Element $element): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return ZoomBarAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/zoombar.html.twig';
    }
}
