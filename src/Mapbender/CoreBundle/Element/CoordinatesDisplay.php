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
        return 'Coordinates display';
    }

    public static function getClassDescription()
    {
        return 'The coordinates display shows your mouse position in map coordinates.';
    }

    public static function getClassTags()
    {
        return array('coordinates', 'display', 'mouse', 'position');
    }

    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.coordinatesdisplay.js'),
            'css' => array('mapbender.element.coordinatesdisplay.css')
        );
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'formatoutput' => true,
            'empty' => 'x= -<br>y= -',
            'displaystring' => '',
            'prefix' => '',
            'separator' => '<br/>y= ',
            'suffix' => '',
            'target' => null);
    }

    public function getWidgetName()
    {
        return 'mapbender.mbCoordinatesDisplay';
    }

    public function render()
    {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:coordinatesdisplay.html.twig', array(
                'id' => $this->getId(),
                'configuration' => $this->getConfiguration()));
    }
}

