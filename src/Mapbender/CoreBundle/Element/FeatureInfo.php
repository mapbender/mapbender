<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * FeatureInfo element
 *
 * @author Christian Wygoda
 */
class FeatureInfo extends Element {
    static public function getClassTitle() {
        return "FeatureInfo";
    }

    static public function getClassDescription() {
        return "Renders a button to trigger a feature info request and popup";
    }

    static public function getClassTags() {
        return array('Button', 'FeatureInfo');
    }

    public static function getDefaultConfiguration() {
        return array(
            'layers' => null,
            'target' => null);
    }

    public function getWidgetName() {
        return 'mapbender.mbFeatureInfo';
    }

    public function getAssets($type) {
        parent::getAssets($type);
        switch($type) {
        case 'js':
            return array(
                'mapbender.element.button.js',
                'mapbender.element.featureInfo.js');
        case 'css':
            return array(
                //TODO: Split up
                'mapbender.elements.css');
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

