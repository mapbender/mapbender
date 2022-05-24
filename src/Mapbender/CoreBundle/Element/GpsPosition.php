<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\Element;

class GpsPosition extends ButtonLike implements ConfigMigrationInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.gpsposition.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.gpsposition.class.description";
    }

    public function getRequiredAssets(Element $element)
    {
        $required = parent::getRequiredAssets($element) + array(
            'js' => array(),
            'trans' => array(),
        );
        $required['js'] = array_merge($required['js'], array(
            '@MapbenderCoreBundle/Resources/public/mapbender.element.gpsPosition.js',
            // Uncomment to enable Geolocation API mock
            // '@MapbenderCoreBundle/Resources/public/GeolocationMock.js',
        ));
        $required['trans'] = array_merge($required['trans'], array(
            'mb.core.gpsposition.*',
        ));
        return $required;
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
        return array_replace(parent::getDefaultConfiguration(), array(
            'autoStart'             => false,
            'icon' => 'iconGps',
            'average'               => 1,
            'follow'                => false,
            'centerOnFirstPosition' => true,
            'zoomToAccuracyOnFirstPosition' => true,
        ));
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbGpsPosition';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:gpsposition.html.twig');
        $this->initializeView($view, $element);
        $view->attributes['class'] = 'mb-button mb-gpsButton';
        return $view;

    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:gpsposition.html.twig';
    }

    public static function updateEntityConfig(Entity\Element $entity)
    {
        $config = $entity->getConfiguration() ?: array();
        if (!empty($config['zoomToAccuracy']) && isset($config['centerOnFirstPosition'])) {
            $config['zoomToAccuaryOnFirstPosition'] = $config['centerOnFirstPosition'];
        }
        unset($config['zoomToAccuracy']);
        $entity->setConfiguration($config);
    }
}
