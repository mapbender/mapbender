<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * 
 */
class GpsPosition extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "GPS-Position";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Renders a button to show the GPS-Position";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('GPS','Position');
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.button.js',
                'mapbender.element.gpsPosition.js'),
            'css' => array('mapbender.element.gpsPosition.css'));
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\GpsPositionAdminType';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => "GPS-Position",
            'label' => true,
            'icon' => 'gpsposition',
            'autoStart' => false,
            'target' => null,
            'refreshinterval' => '5000');
    }
    
    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbGpsPosition';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = $this->getConfiguration();
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:gpsposition.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'configuration' => $configuration,
                            'title' => $this->getTitle()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:gpsposition.html.twig';
    }
}

