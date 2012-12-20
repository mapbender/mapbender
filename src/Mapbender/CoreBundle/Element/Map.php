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
            "scales" => "25000000,10000000,5000000,1000000,500000",
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

        $allsrs = explode(",", $configuration["srs"] . (strlen(trim($configuration["otherSrs"])) > 0 ? "," . $configuration["otherSrs"] : ""));
        unset($configuration['otherSrs']);
        $em = $this->container->get("doctrine")->getEntityManager();
        $query = $em->createQuery("SELECT srs FROM MapbenderCoreBundle:SRS srs"
                . " Where srs.name IN (:name)  ORDER BY srs.id ASC")->setParameter('name', $allsrs);
        $srses = $query->getResult();
        foreach ($srses as $srsTemp) {
            $configuration["srsDefs"][$srsTemp->getName()] = array(
                "name" => $srsTemp->getName(),
                "title" => $srsTemp->getTitle(),
                "definition" => $srsTemp->getDefinition());
        }
//        $configuration["srsDefs"] = $srsDefs;

        if(!isset($configuration['scales'])){
            throw new \RuntimeException('The scales does not defined.');
        } else if($configuration['scales'] !== null && is_string($configuration['scales'])){
            $configuration['scales'] = explode(",", $configuration['scales']);
        }

        if($srs) {
            if(!isset($configuration["srsDefs"][$srs])){
                throw new \RuntimeException('The srs: "' . $srs
                    . '" does not supported.');
            }
            $configuration = array_merge($configuration,
                array('targetsrs' => $srs));
        }
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

