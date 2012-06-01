<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Button element
 *
 * @author Christian Wygoda
 */
class Button extends Element {
    static public function getClassTitle() {
        return "Button";
    }

    static public function getClassDescription() {
        return "Renders a button";
    }

    static public function getClassTags() {
        return array('Button');
    }

    public static function getDefaultConfiguration() {
        return array(
            'target' => null,
            'click' => null,
            'icon' => null,
            'label' => true,
            'group' => null);
    }

    public function getWidgetName() {
        return 'mapbender.mbButton';
    }

    public function getAssets($type) {
        parent::getAssets($type);
        switch($type) {
        case 'js':
            return array('mapbender.element.button.js');
        case 'css':
            //TODO: Split up
            return array('mapbender.elements.css');
        }
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:button.html.twig', array(
                'id' => $this->getId(),
                'label' => $this->getTitle(),
                'configuration' => $this->entity->getConfiguration()));
    }
}

