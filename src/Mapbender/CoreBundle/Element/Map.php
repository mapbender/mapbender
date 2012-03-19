<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Map extends Element implements ElementInterface {
    static public function getTitle() {
        return "Map";
    }

    static public function getDescription() {
        return "OpenLayers based map";
    }

    static public function getTags() {
        return array('Map', 'MapQuery');
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapquery/lib/openlayers/OpenLayers.js',
                'mapquery/lib/jquery/jquery.tmpl.js',
                'mapquery/src/jquery.mapquery.core.js',
                'mapbender.element.map.js'
            ),
            'css' => array(
                'mapbender.elements.css',
                'mapquery/lib/openlayers/theme/default/style.css'
            )
        );
    }

    public function getConfiguration() {
        //TODO: Cherry pick

        $extra = array();
        $srs = $this->get('request')->get('srs');
        $poi = $this->get('request')->get('poi');
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

        $bbox = $this->get('request')->get('bbox');
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

        $options = array_merge(array('extra' => $extra), $this->configuration);
        if($srs)
          $options = array_merge($options, array('targetsrs' => $srs));
        return array(
            'options' => $options,
            'init' => 'mbMap',
        );
    }

    public function render() {
            return $this->get('templating')->render('MapbenderCoreBundle:Element:map.html.twig', array(
                'id' => $this->id
            ));
    }
}

