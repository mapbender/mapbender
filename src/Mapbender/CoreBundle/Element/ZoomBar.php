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

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "Navigation Toolbar";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "The Navigation Toolbar element provides a floating control to pan and zoom,
similar to the OpenLayers PanZoomBar control. This element though is easier to
use when custom styling is needed.";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array('zoom', 'pan', 'control', 'navigation', 'panel');
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array('js' => array('mapbender.element.zoombar.js'),'css' => array());
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => null,
            'target' => null,
            'components' => array(
                "pan" => "pan",
                "history" => "history",
                "zoom_box" => "zoom box",
                "zoom_max" => "zoom to max extent",
                "zoom_slider" => "zoom slider"),
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

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:zoombar.html.twig',
                                 array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
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

