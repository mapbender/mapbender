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
        return <<<EOT
The Navigation Toolbar element provides a floating control to pan and zoom,
similar to the OpenLayers PanZoomBar control. This element though is easier to
use when custom styling is needed.
EOT;
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
        return array(
            'js' => array('mapbender.element.zoombar.js'),
            'css' => array('mapbender.element.zoombar.css'));
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => null,
            'target' => null,
            'components' => array(),
            'stepSize' => 50,
            'stepByPixel' => false,
            'position' => array(0, 0),
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

}

