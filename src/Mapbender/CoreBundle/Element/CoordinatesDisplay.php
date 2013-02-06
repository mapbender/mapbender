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
    public static function getClassTitle()
    {
        return 'Coordinates Display';
    }

    public static function getClassDescription()
    {
        return 'The coordinates display shows your mouse position in map coordinates.';
    }

    public static function getClassTags()
    {
        return array('coordinates', 'display', 'mouse', 'position');
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\CoordinatesDisplayAdminType';
    }

    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.coordinatesdisplay.js'),
            'css' => array('mapbender.elements.css')
        );
    }

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

    public function getWidgetName()
    {
        return 'mapbender.mbCoordinatesDisplay';
    }

    public function render()
    {
        $a = $this->getConfiguration();
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:coordinatesdisplay.html.twig', array(
                'id' => $this->getId(),
                'title' => $this->getTitle(),
                'configuration' => $this->getConfiguration()));
    }
}

