<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Mapbender Zoombar
 *
 * The Zoombar element provides a control to pan and zoom, similar to the
 * OpenLayers PanZoomBar control. This element though is easier to use when
 * custom styling is needed.
 *
 * @author Christian Wygoda
 */
class ZoomBar extends Element
{

    public static $merge_configurations = false;

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
    public static function getClassTags()
    {
        return array(
            "mb.core.zoombar.tag.zoom",
            "mb.core.zoombar.tag.pan",
            "mb.core.zoombar.tag.control",
            "mb.core.zoombar.tag.navigation",
            "mb.core.zoombar.tag.panel");
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
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
            'tooltip' => null,
            'target' => null,
            'components' => array("pan", "history", "zoom_box", "zoom_max", "zoom_in_out", "zoom_slider"),
            'anchor' => 'left-top',
            'stepSize' => 50,
            'stepByPixel' => false,
            'draggable' => true);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbZoomBar';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:zoombar.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = $this->getConfiguration();
        if (in_array("zoom_slider", $configuration['components'])
            && !in_array("zoom_in_out", $configuration['components'])) {
            $configuration['components'][] = "zoom_in_out";
        }
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(),  array(
            'id' => $this->getId(),
            "title" => $this->getTitle(),
            'configuration' => $configuration,
        ));
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
