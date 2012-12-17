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
            'otherSrs' => array(),
            'units' => 'degrees',
            'extents' => array(
                'max' => array(-180, -90, 180, 90),
                'start' => array(-180, -90, 180, 90)),
            'maxResolution' => 'auto',
            "scales:" => array(25000000, 10000000,5000000,1000000,500000),
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
                'proj4js/proj4js-compressed.js',
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

        // @TODO: Move into DataTransformer of MapAdminType
        $configuration = array_merge(array('extra' => $extra), $configuration);
        
        if(!isset($configuration['otherSrs']) || $configuration['otherSrs'] === null){
            $configuration['otherSrs'] = array();
        } elseif(is_string($configuration['otherSrs'])) {
            $configuration['otherSrs'] = explode(',', $configuration['otherSrs']);
        }
        
        if(isset($configuration['scales']) && $configuration['scales'] !== null && is_string($configuration['scales'])){
            $configuration['scales'] = explode(",", $configuration['scales']);
        }
//        else {
//            unset($configuration['scales']);
//        }

        if($srs) {
            foreach (explode(",", $configuration['otherSrs']) as $value) {
                if(strtoupper($value) === strtoupper($srs)){
                    $found = true;
                    break;
                }
            }
            if($found === false){
                throw new \RuntimeException('The srs: "' . $srs
                    . '" does not supported.');
            }
            $configuration = array_merge($configuration,
                array('targetsrs' => $srs));
        }
        $defSRS = $configuration["srs"];
        $srsDefs = array();
        // TEMP START @TODO from database
        $temp = array(
            "EPSG:4326" => array("name" => "EPSG:4326", "title" => "WGS 84", "definition" => "+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs"),
            "EPSG:31466" => array("name" => "EPSG:31466", "title" => "3-degree Gauss-Kruger zone 2", "definition" => "+proj=tmerc +lat_0=0 +lon_0=6 +k=1 +x_0=2500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs"),
            "EPSG:31467" => array("name" => "EPSG:31467", "title" => "3-degree Gauss-Kruger zone 3", "definition" => "+proj=tmerc +lat_0=0 +lon_0=9 +k=1 +x_0=3500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs"),
            "EPSG:31468" => array("name" => "EPSG:31468", "title" => "3-degree Gauss-Kruger zone 4", "definition" => "+proj=tmerc +lat_0=0 +lon_0=12 +k=1 +x_0=4500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs"),
            "EPSG:25832" => array("name" => "EPSG:25832", "title" => "UTM zone 32N", "definition" => "+proj=utm +zone=32 +ellps=GRS80 +units=m +no_defs"),
            "EPSG:25833" => array("name" => "EPSG:25833", "title" => "UTM zone 33N", "definition" => "+proj=utm +zone=33 +ellps=GRS80 +units=m +no_defs"),
        );
        $srsDefs[$defSRS] = $temp[$defSRS];
        foreach($configuration['otherSrs'] as $othersrs){
            $srsDefs[$othersrs] = $temp[$othersrs];
        }
        unset($configuration['otherSrs']);
        // TEMP END
        $configuration["srsDefs"] = $srsDefs;
        return $configuration;
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:map.html.twig', array(
                'id' => $this->getId()));
    }


    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\MapAdminType';
    }
}

