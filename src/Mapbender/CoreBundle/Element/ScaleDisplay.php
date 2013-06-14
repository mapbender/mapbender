<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class ScaleDisplay extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "ScaleDisplay";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "ScaleDisplay";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('ScaleDisplay', "Map's scale dispaly");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Scale Display',
            'tooltip' => 'Scale Display',
            'target' => null,
            'anchor' => 'right-bottom',
            'position' => array('20px', '20px'));
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbScaledisplay';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleDisplayAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.scaledisplay.js'),
            'css' => array('mapbender.element.scaledisplay.css'));
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:scaledisplay.html.twig',
                                 array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

}

