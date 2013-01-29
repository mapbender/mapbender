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
            'title' => 'button',
            'tooltip' => 'button',
            'label' => true,
            'icon' => null,
            'target' => null,
            'click' => null,
            'group' => null,
            'action' => null);
    }

    public function getWidgetName() {
        return 'mapbender.mbButton';
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ButtonAdminType';
    }

    public function getAssets() {
        return array(
            'js' => array('mapbender.element.button.js'),
            //TODO: Split up
            'css' => array('mapbender.elements.css'));
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:button.html.twig', array(
                'id' => $this->getId(),
                'title' => $this->getTitle(),
                'configuration' => $this->entity->getConfiguration()));
    }
}

