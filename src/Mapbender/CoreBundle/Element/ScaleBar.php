<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class ScaleLine extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "ScaleLine";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "ScaleLine";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('ScaleLine', "Map's scale line");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Scale Line',
            'tooltip' => 'Scale Line',
            'target' => null,
            'maxWidth' => 200,
            'position' => array(0, 0),
            'anchor' => array(
                'inline',
                'left-top',
                'left-bottom',
                'right-top',
                'right-bottom'));
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbScaleline';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleLineAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.scaleline.js'),
            //TODO: Split up
            'css' => array('mapbender.element.scaleline.css'));
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:scaleline.html.twig',
                                 array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

}

