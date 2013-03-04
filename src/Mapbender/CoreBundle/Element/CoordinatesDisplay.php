<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Coordinates display
 *
 * Displays the mouse coordinates
 *
 * @author Paul Schmidt
 * @author Christian Wygoda
 */
class CoordinatesDisplay extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return 'Coordinates Display';
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return 'The coordinates display shows your mouse position in map coordinates.';
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array('coordinates', 'display', 'mouse', 'position');
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\CoordinatesDisplayAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.coordinatesdisplay.js'),
            'css' => array('mapbender.elements.css')
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'coordinates display',
            'label' => false,
//            'formatoutput' => true,
            'empty' => 'x= - y= -',
//            'displaystring' => '',
            'prefix' => 'x= ',
            'separator' => ' y= ',
//            'suffix' => '',
            'target' => null
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbCoordinatesDisplay';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $a = $this->getConfiguration();
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:coordinatesdisplay.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

}

