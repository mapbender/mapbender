<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Table of Content element
 *
 * @author Christian Wygoda
 */
class Toc extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Table of Contents";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Table of contents listing map layers";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('TOC', 'Table of Contents');
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Table of Contents',
            'target' => null,
            'autoOpen' => true,
            'tooltip' => "Table of contents");
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbToc';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\TocAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.toc.js'),
            //TODO: Split up
            'css' => array('mapbender.elements.css'));
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:toc.html.twig',
                                 array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

}

