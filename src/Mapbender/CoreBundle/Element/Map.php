<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Map element.
 *
 * @author Christian Wygoda
 */
class Map extends Element {
    static public function getClassTitle() {
        return "Map";
    }

    static public function getClassDescription() {
        return "MapQuery/OpenLayers based map";
    }

    static public function getClassTags() {
        return array('Map', 'MapQuery', 'OpenLayers');
    }

    public static function getDefaultConfiguration() {
        return array(
            'layerset' => null,
            'dpi' => 72,
            'srs' => 'EPSG:4326',
            'units' => 'degrees',
            'extents' => array(
                'max' => array(-180, -90, 180, 90),
                'start' => array(-180, -90, 180, 90)),
            'maxResolution' => 'auto',
            'imgPath' => 'bundles/mapbendercore/mapquery/lib/openlayers/img');
    }

    public function getWidgetName() {
        return 'mapbender.mbMap';
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapquery/lib/openlayers/OpenLayers.js',
                'mapquery/lib/jquery/jquery.tmpl.js',
                'mapquery/src/jquery.mapquery.core.js',
                'mapbender.element.map.js'),
            'css' => array(
                //TODO: Split up
                'mapbender.elements.css',
                'mapquery/lib/openlayers/theme/default/style.css'));
    }

    public function getConfiguration() {
        $configuration = parent::getConfiguration();

        $extra = array();
        $srs = $this->container->get('request')->get('srs');
        $poi = $this->container->get('request')->get('poi');
        if($poi) {
            $extra['type'] = 'poi';
            $point = split(',', $poi['point']);
            $extra['data'] = array(
                'x' => floatval($point[0]),
                'y' => floatval($point[1]),
                'label' => $poi['label'],
                'scale' => $poi['scale']
            );
        }

        $bbox = $this->container->get('request')->get('bbox');
        if(!$poi && $bbox) {
            $bbox = explode(',', $bbox);
            if(count($bbox) === 4) {
                $extra['type'] = 'bbox';
                $extra['data'] = array(
                    'xmin' => floatval($bbox[0]),
                    'ymin' => floatval($bbox[1]),
                    'xmax' => floatval($bbox[2]),
                    'ymax' => floatval($bbox[3])
                );
            }
        }

        $configuration = array_merge(array('extra' => $extra), $configuration);

        if($srs)
            $configuration = array_merge($configuration,
                array('targetsrs' => $srs));

        return $configuration;
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:map.html.twig', array(
                'id' => $this->getId()));
    }
}

