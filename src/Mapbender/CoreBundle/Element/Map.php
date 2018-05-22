<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Map element.
 *
 * @author Christian Wygoda
 */
class Map extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.map.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.mapabs.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.map.tag.map",
            "mb.core.map.tag.mapquery",
            "mb.core.map.tag.openlayers");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        /* "standardized rendering pixel size" for WMTS 0.28 mm × 0.28 mm -> DPI for WMTS: 90.714285714 */
        return array(
            'layersets' => array(),
            'dpi' => 90.714, // DPI for WMTS: 90.714285714
            'srs' => 'EPSG:4326',
            'otherSrs' => array("EPSG:31466", "EPSG:31467"),
            'units' => 'degrees',
            'wmsTileDelay' => 2500,
            'tileSize' => 512,
            'minTileSize' => 128,
            'extents' => array(
                'max' => array(0, 40, 20, 60),
                'start' => array(5, 45, 15, 55)),
            'maxResolution' => 'auto',
            "scales" => array(25000000, 10000000, 5000000, 1000000, 500000),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbMap';
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                '/../vendor/mapbender/mapquery/lib/openlayers/OpenLayers.js',
                /* 'mapquery/lib/openlayers/lib/deprecated.js', */
                '/../vendor/mapbender/mapquery/lib/jquery/jquery.tmpl.js',
                '/../vendor/mapbender/mapquery/src/jquery.mapquery.core.js',
                'proj4js/proj4js-compressed.js',
                'mapbender.element.map.mapaxisorder.js',
                'mapbender.element.map.js'),
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/map.scss'));
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $defaultConfiguration = $this->getDefaultConfiguration();
        $configuration        = parent::getConfiguration();
        $extra                = array();
        // @TODO: Move into DataTransformer of MapAdminType
        $configuration        = array_merge(array('extra' => $extra), $configuration);
        $allsrs               = array();
        if (is_int(stripos($configuration["srs"], "|"))) {
            $srsHlp               = preg_split("/\s?\|{1}\s?/", $configuration["srs"]);
            $configuration["srs"] = trim($srsHlp[0]);
            $allsrs[]             = array(
                "name" => trim($srsHlp[0]),
                "title" => strlen(trim($srsHlp[1])) > 0 ? trim($srsHlp[1]) : '');
        } else {
            $configuration["srs"] = trim($configuration["srs"]);
            $allsrs[]             = array(
                "name" => $configuration["srs"],
                "title" => '');
        }

        if (isset($configuration["otherSrs"])) {
            if (is_array($configuration["otherSrs"])) {
                $otherSrs = $configuration["otherSrs"];
            } elseif (is_string($configuration["otherSrs"]) && strlen(trim($configuration["otherSrs"])) > 0) {
                $otherSrs = preg_split("/\s?,\s?/", $configuration["otherSrs"]);
            }
            foreach ($otherSrs as $srs) {
                if (is_int(stripos($srs, "|"))) {
                    $srsHlp   = preg_split("/\s?\|{1}\s?/", $srs);
                    $allsrs[] = array(
                        "name" => trim($srsHlp[0]),
                        "title" => strlen(trim($srsHlp[1])) > 0 ? trim($srsHlp[1]) : '');
                } else {
                    $allsrs[] = array(
                        "name" => $srs,
                        "title" => '');
                }
            }
        }
        $allsrs                   = array_unique($allsrs, SORT_REGULAR);
        $configuration["srsDefs"] = $this->getSrsDefinitions($allsrs);
        $srs_req                  = $this->container->get('request')->get('srs');
        if ($srs_req) {
            $exists = false;
            foreach ($allsrs as $srsItem) {
                if (strtoupper($srsItem['name']) === strtoupper($srs_req)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $this->container->get('logger')->error(
                    'The requested srs ' . $srs_req . ' is not supported by this application.');
            } else {
                $configuration = array_merge($configuration, array('targetsrs' => strtoupper($srs_req)));
            }
        }

        $pois = $this->container->get('request')->get('poi');
        if ($pois) {
            $extra['pois'] = array();
            if (array_key_exists('point', $pois)) {
                $pois = array($pois);
            }
            foreach ($pois as $poi) {
                $point = explode(',', $poi['point']);
                $help  = array(
                    'x' => floatval($point[0]),
                    'y' => floatval($point[1]),
                    'label' => isset($poi['label']) ? htmlentities($poi['label']) : null,
                    'scale' => isset($poi['scale']) ? intval($poi['scale']) : null
                );
                if (!empty($poi['srs'])) {
                    $help['srs'] = $poi['srs'];
                }
                $extra['pois'][] = $help;
            }
        }

        $bbox = $this->container->get('request')->get('bbox');
        if (!isset($extra['pois']) && $bbox) {
            $bbox = explode(',', $bbox);
            if (count($bbox) === 4) {
                $extra['bbox'] = array(
                    floatval($bbox[0]),
                    floatval($bbox[1]),
                    floatval($bbox[2]),
                    floatval($bbox[3])
                );
            }
        }

        $center    = $this->container->get('request')->get('center');
        $centerArr = $center !== null ? explode(',', $center) : null;
        if ($center !== null && is_array($centerArr) && count($centerArr) === 2) {
            $configuration["center"] = $centerArr;
        }

        $configuration['extra'] = $extra;
        if (!isset($configuration['layersets']) && isset($configuration['layerset'])) {# "layerset" deprecated start
            $configuration['layersets'] = array($configuration['layerset']);
        }# "layerset" deprecated end
        if ($scale = $this->container->get('request')->get('scale')) {
            $scale  = intval($scale);
            $scales = $configuration['scales'];
            if ($scale > $scales[0]) {
                $scale = $scales[0];
            } elseif ($scale < $scales[count($scales) - 1]) {
                $scale = $scales[count($scales) - 1];
            } else {
                $tmp = null;
                for ($idx = count($scales) - 2; ($idx >= 0 && count($scales) > 1); $idx--) {
                    if ($scale >= $scales[$idx + 1] && $scale <= $scales[$idx]) {
                        $tmp = (($scales[$idx] - $scales[$idx + 1]) / 2 >= $scale - $scales[$idx + 1]) ?
                            $scales[$idx + 1] : $scales[$idx];
                    }
                }
                $scale = $tmp ? $tmp : $scales[0];
            }
            $configuration['targetscale'] = $scale;
        }

        if (!isset($configuration["tileSize"])) {
            $configuration["tileSize"] = $defaultConfiguration["tileSize"];
        } elseif ($configuration["tileSize"] < $defaultConfiguration["minTileSize"]) {
            $configuration["tileSize"] = $defaultConfiguration["minTileSize"];
        }

        return $configuration;
    }

    public function getPublicConfiguration()
    {
        return array_replace($this->getConfiguration(), array(
            'imgPath' => 'components/mapquery/lib/openlayers/img',
        ));
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:map.html.twig', array(
                    'id' => $this->getId()));
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\MapAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:map.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        $response = new Response();
        switch ($action) {
            case 'loadsrs':
                $data = $this->loadSrsDefinitions();
                $response->setContent(json_encode($data));
                $response->headers->set('Content-Type', 'application/json');
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
        return $response;
    }

    /**
     * Returns proj4js srs definitions from a GET parameter srs
     * @return array srs definitions
     */
    protected function loadSrsDefinitions()
    {
        $srsList = $this->container->get('request')->get("srs", null);
        $srses   = preg_split("/\s?,\s?/", $srsList);
        $allsrs  = array();
        foreach ($srses as $srs) {
            if (is_int(stripos($srs, "|"))) {
                $srsHlp   = preg_split("/\s?\|{1}\s?/", $srs);
                $allsrs[] = array(
                    "name" => trim($srsHlp[0]),
                    "title" => strlen(trim($srsHlp[1])) > 0 ? trim($srsHlp[1]) : '');
            } else {
                $allsrs[] = array(
                    "name" => trim($srs),
                    "title" => '');
            }
        }
        $result = $this->getSrsDefinitions($allsrs);
        if (count($result) > 0) {
            return array("data" => $result);
        } else {
            return array("error" => $this->trans("mb.core.map.srsnotfound", array('%srslist%', $srsList)));
        }
    }

    /**
     * Returns proj4js srs definitions from srs names
     * @param array $srsNames srs names (array with "EPSG" codes)
     * @return array proj4js srs definitions
     */
    protected function getSrsDefinitions(array $srsNames)
    {
        $result = array();
        if (is_array($srsNames) && count($srsNames) > 0) {
            $names = array();
            foreach ($srsNames as $srsName) {
                $names[] = $srsName['name'];
            }
            $em    = $this->container->get("doctrine")->getManager();
            $query = $em->createQuery("SELECT srs FROM MapbenderCoreBundle:SRS srs"
                    . " Where srs.name IN (:name)  ORDER BY srs.id ASC")
                ->setParameter('name', $names);
            $srses = $query->getResult();
            foreach ($srsNames as $srsName) {
                foreach ($srses as $srs) {
                    if ($srsName['name'] === $srs->getName()) {
                        $result[] = array(
                            "name" => $srs->getName(),
                            "title" => strlen($srsName["title"]) > 0 ? $srsName["title"] : $srs->getTitle(),
                            "definition" => $srs->getDefinition());
                        break;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        if (key_exists('extent_start', $configuration) && key_exists('extent_start', $configuration)) {
            $configuration['extents'] = array(
                'start' => $configuration['extent_start'],
                'max' => $configuration['extent_max']
            );
            unset($configuration['extent_start']);
            unset($configuration['extent_max']);
        }
        if (is_string($configuration['otherSrs'])) {
            $configuration['otherSrs'] = explode(',', $configuration['otherSrs']);
        }
        if (is_string($configuration['scales'])) {
            $configuration['scales'] = explode(',', $configuration['scales']);
        }

        if (key_exists('layersets', $configuration)) {
            foreach ($configuration['layersets'] as &$layerset) {
                $layerset = $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\Layerset', $layerset);
            }
        }
        return $configuration;
    }
}
