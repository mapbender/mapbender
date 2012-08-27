<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Activity indicator
 *
 * @author Christian Wygoda
 */
class ActivityIndicator extends Element {
    static function getClassTitle() {
        return "Activity Indicator";
    }

    static function getClassDescription() {
        return "Shows HTTP activity";
    }

    static function getClassTags() {
        return array();
    }

    static function getDefaultConfiguration() {
        return array(
            'activityClass' => 'mb-activity',
            'ajaxActivityClass' => 'mb-activity-ajax',
            'tileActivityClass' => 'mb-activity-tile');
    }

    public function getWidgetName() {
        return 'mapbender.mbActivityIndicator';
    }

    public function getAssets() {
        return array(
            'js' => array('mapbender.element.activityindicator.js'),
            //TODO: Split up
            'css' => array('mapbender.elements.css'));
    }

     public function render() {
         return $this->get('templating')
             ->render('MapbenderCoreBundle:Element:activityindicator.html.twig',
                 array(
                    'id' => $this->id,
                    'configuration' => $this->configuration));
    }
}

