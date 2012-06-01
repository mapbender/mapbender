<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Table of Content element
 *
 * @author Christian Wygoda
 */
class Toc extends Element {
    static public function getClassTitle() {
        return "Table of contents";
    }

    static public function getClassDescription() {
        return "Table of contents listing map layers";
    }

    static public function getClassTags() {
        return array('TOC', 'Table of Contents');
    }

    public static function getDefaultConfiguration() {
        return array(
            'target' => null,
            'autoOpen' => true);
    }

    public function getWidgetName() {
        return 'mapbender.mbToc';
    }

    public function getAssets($type) {
        parent::getAssets($type);
        switch($type) {
        case 'js':
            return array('mapbender.element.toc.js');
        case 'css':
            //TODO: Split up
            return array('mapbender.elements.css');

        }
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:toc.html.twig', array(
                'id' => $this->getId(),
                'configuration' => $this->getConfiguration()));
    }
}

