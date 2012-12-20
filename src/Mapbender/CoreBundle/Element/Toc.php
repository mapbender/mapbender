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
            'title: ' => 'Table of Content',
            'target' => null,
            'autoOpen' => true,
            'tooltip' => "Table of content");
    }

    public function getWidgetName() {
        return 'mapbender.mbToc';
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\TocAdminType';
    }

    public function getAssets() {
        return array(
            'js' => array('mapbender.element.toc.js'),
            //TODO: Split up
            'css' => array('mapbender.elements.css'));
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:toc.html.twig', array(
                'id' => $this->getId(),
                "title" => $this->getTitle(),
                'configuration' => $this->getConfiguration()));
    }
}

