<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
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
    static public function getClassTitle()
    {
        return "mb.core.map.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.core.mapabs.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
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
        return array(
            'layerset' => null,
            'dpi' => 72,
            'srs' => 'EPSG:4326',
            'otherSrs' => array("EPSG:31466", "EPSG:31467"),
            'units' => 'degrees',
            'extents' => array(
                'max' => array(0, 40, 20, 60),
                'start' => array(5, 45, 15, 55)),
            'maxResolution' => 'auto',
            "scales" => array(25000000, 10000000, 5000000, 1000000, 500000),
            'imgPath' => 'bundles/mapbendercore/mapquery/lib/openlayers/img');
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
    static public function listAssets()
    {
        return array(
            'js' => array(
                'mapquery/lib/openlayers/OpenLayers.js',
                'mapquery/lib/jquery/jquery.tmpl.js',
                'mapquery/src/jquery.mapquery.core.js',
                'proj4js/proj4js-compressed.js',
                'mapbender.element.map.js'),
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/map.scss'));
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();

        if (isset($configuration["scales"])) {
            $scales = array();
            if (is_string($configuration["scales"])) { // from database
                $scales = preg_split("/\s?[\,\;]\s?/", $configuration["scales"]);
            } else if (is_array($configuration["scales"])) { // from twig
                $scales = $configuration["scales"];
            }
            // sort scales high to low
            $scales = array_map(
                create_function('$value', 'return (int)$value;'), $scales);
            arsort($scales, SORT_NUMERIC);
            $configuration["scales"] = $scales;
        }

        $extra = array();

        // @TODO: Move into DataTransformer of MapAdminType
        $configuration = array_merge(array('extra' => $extra), $configuration);
        $allsrs = array();
        if (is_int(stripos($configuration["srs"], "|"))) {
            $srsHlp = preg_split("/\s?\|{1}\s?/", $configuration["srs"]);
            $configuration["srs"] = trim($srsHlp[0]);
            $allsrs[] = array(
                "name" => trim($srsHlp[0]),
                "title" => strlen(trim($srsHlp[1])) > 0 ? trim($srsHlp[1]) : '');
        } else {
            $configuration["srs"] = trim($configuration["srs"]);
            $allsrs[] = array(
                "name" => $configuration["srs"],
                "title" => '');
        }

        if (isset($configuration["otherSrs"])) {
            if (is_array($configuration["otherSrs"])) {
                $otherSrs = $configuration["otherSrs"];
            } else if (is_string($configuration["otherSrs"]) && strlen(trim($configuration["otherSrs"]))
                > 0) {
                $otherSrs = preg_split("/\s?,\s?/", $configuration["otherSrs"]);
            }
            foreach ($otherSrs as $srs) {
                if (is_int(stripos($srs, "|"))) {
                    $srsHlp = preg_split("/\s?\|{1}\s?/", $srs);
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

        $configuration["srsDefs"] = $this->getSrsDefinitions($allsrs);
        $srs_req = $this->container->get('request')->get('srs');
        if ($srs_req) {
            $exists = false;
            foreach ($allsrs as $srsItem) {
                if (strtoupper($srsItem['name']) === strtoupper($srs_req)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                throw new \RuntimeException('The srs: "' . $srs_req
                . '" does not supported.');
            }
            $configuration = array_merge($configuration, array('targetsrs' => $srs_req));
        }

        $pois = $this->container->get('request')->get('poi');
        if ($pois) {
            $extra['pois'] = array();
            if (array_key_exists('point', $pois)) {
                $pois = array($pois);
            }
            foreach ($pois as $poi) {
                $point = explode(',', $poi['point']);
                $extra['pois'][] = array(
                    'x' => floatval($point[0]),
                    'y' => floatval($point[1]),
                    'label' => isset($poi['label']) ? $poi['label'] : null,
                    'scale' => isset($poi['scale']) ? intval($poi['scale']) : null
                );
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

        $configuration['extra'] = $extra;

        if (!isset($configuration['scales'])) {
            throw new \RuntimeException('The scales does not defined.');
        } else if (is_string($configuration['scales'])) {
            $configuration['scales'] = preg_split(
                "/\s?,\s?/", $configuration['scales']);
        }
        return $configuration;
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

    public function httpAction($action)
    {
        $response = new Response();
        switch ($action) {
            case 'loadsrs':
                $data = $this->loadSrsDefinitions();
                $response->setContent(json_encode($data));
                $response->headers->set('Content-Type', 'application/json');
                break;
            case 'metadata':
                $metadata = $this->getMetadata();
                $metadata['content'] = "raw";
                $html = $this->container->get('templating')
                    ->render('MapbenderCoreBundle::metadata.html.twig', array('metadata' => $metadata));
                $response->setContent($html);
                $response->headers->set('Content-Type', 'text/html');
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
        return $response;
    }

    private function getMetadata()
    {
        $result = array(
            "display" => "notab",
            "sections" => array()
        );
        $sourceId = $this->container->get('request')->get("sourceId", null);
        $layerId = $this->container->get('request')->get("layerId", null);
        $repository = 'Mapbender\CoreBundle\Entity\SourceInstance';
        $instance = $this->container->get("doctrine")->getRepository($repository)
            ->find($sourceId);
        $source = $instance->getSource();
        
        $source_items = array();
        $source_items[] = array("title" => Map::getNotNull($source->getTitle()));
        $source_items[] = array("name" => Map::getNotNull($source->getName()));
        $source_items[] = array("version" => Map::getNotNull($source->getVersion()));
        $source_items[] = array("originUrl" => Map::getNotNull($source->getOriginUrl()));
        $source_items[] = array("description" => Map::getNotNull($source->getDescription()));
        $source_items[] = array("onlineResource" => Map::getNotNull($source->getOnlineResource() !== null ? $source->getOnlineResource()->getHref() : ""));
        $source_items[] = array("exceptionFormats" => Map::getNotNull(implode(",", $source->getExceptionFormats())));
        $source_items[] = array("fees" => Map::getNotNull($source->getFees()));
        $source_items[] = array("accessconstraints" => Map::getNotNull($source->getAccessConstraints()));
        $result["sections"][] = array(
            "title" => "common",
            "items" => $source_items
        );
        
        
        $contact = $source->getContact();
        $contact_items = array();
        $contact_items[] = array("person" => Map::getNotNull($contact->getPerson()));
        $contact_items[] = array("position" => Map::getNotNull($contact->getPosition()));
        $contact_items[] = array("organization" => Map::getNotNull($contact->getOrganization()));

        $contact_items[] = array("voiceTelephone" => Map::getNotNull($contact->getVoiceTelephone()));
        $contact_items[] = array("facsimileTelephone" => Map::getNotNull($contact->getFacsimileTelephone()));
        $contact_items[] = array("electronicMailAddress" => Map::getNotNull($contact->getElectronicMailAddress()));
        $contact_items[] = array("address" => Map::getNotNull($contact->getAddress()));
        $contact_items[] = array("addressType" => Map::getNotNull($contact->getAddressType()));
        $contact_items[] = array("addressCity" => Map::getNotNull($contact->getAddressCity()));
        $contact_items[] = array("addressStateOrProvince" => Map::getNotNull($contact->getAddressStateOrProvince()));
        $contact_items[] = array("addressPostCode" => Map::getNotNull($contact->getAddressPostCode()));
        $contact_items[] = array("addressCountry" => Map::getNotNull($contact->getAddressCountry()));
        $result["sections"][] = array(
            "title" => "contact",
            "items" => $contact_items
        );

        return $result;
    }

    private static function getNotNull($value)
    {
        return $value !== null ? $value : "";
    }

    protected function loadSrsDefinitions()
    {
        $srsList = $this->container->get('request')->get("srs", null);
        $srses = preg_split("/\s?,\s?/", $srsList);
        $allsrs = array();
        foreach ($srses as $srs) {
            if (is_int(stripos($srs, "|"))) {
                $srsHlp = preg_split("/\s?\|{1}\s?/", $srs);
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

    protected function getSrsDefinitions(array $srsNames)
    {
        $result = array();
        if (is_array($srsNames) && count($srsNames) > 0) {
            $names = array();
            foreach ($srsNames as $srsName) {
                $names[] = $srsName['name'];
            }
            $em = $this->container->get("doctrine")->getEntityManager();
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

}
