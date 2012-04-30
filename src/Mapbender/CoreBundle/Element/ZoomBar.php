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
class ZoomBar extends Element {
    public static function getClassTitle() {
        return "Pan/Zoom Bar";
    }

    public static function getClassDescription() {
        return <<<EOT
The Zoombar element provides a control to pan and zoom, similar to the
OpenLayers PanZoomBar control. This element though is easier to use when
custom styling is needed.
EOT;
    }

    public static function getClassTags() {
        return array('zoom', 'pan', 'control', 'panel');
    }

    public function getAssets($type) {
        parent::getAssets($type);
        switch($type) {
        case 'js':
            return array('mapbender.element.zoombar.js');
        case 'css':
            return array('mapbender.element.zoombar.css');
        }
    }

    public function getDefaultConfiguration() {
        return array(
            'stepSize' => 50,
            'stepByPixel' => false,
            'position' => array(0, 0),
            'draggable' => true);
    }

    public function getWidgetName() {
        return 'mapbender.mbZoomBar';
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:zoombar.html.twig', array(
                'id' => $this->getId(),
                'configuration' => $this->getConfiguration()));
    }
}

