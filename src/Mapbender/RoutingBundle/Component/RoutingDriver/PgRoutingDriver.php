<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;
use DateTime;
use Exception;

class PgRoutingDriver extends RoutingDriver
{


    /**
     * @var string
     */
    protected $waysTable;

    /**
     * @var string
     */
    protected $verticesTable;

    /**
     * @var string
     */
    protected $weighting;

    /**
     * @var bool
     */
    protected $instructions;

    /**
     * @var string
     */
    protected $streetNameColumn;


    /**
     * @var bool
     */
    protected $directedGraph;

    /**
     * @var string
     */
    protected $hasReverseCost;

    /**
     * @var string
     */
    protected $routingFunc;

    /**
     * @var array
     */
    protected $points;


    /**
     * @var PgRoutingDBInterface
     */
    private $db;
    /**
     * @var mixed
     */
    private $speed;

    protected $srid;

    protected DoctrineRegistry $doctrine;

    public function __construct(DoctrineRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        /*
        parent::__construct($locale, $translator);

        $this->waysTable = $config['wayTable'];
        $this->verticesTable = $config['wayTableVertices'];
        $this->instructions = filter_var($config['instructions'], FILTER_VALIDATE_BOOLEAN);
        //$this->streetNameColumn = $config['label'];
        $this->weighting = $config['weighting'];
        $this->speed = $config["speed"];

        $this->points = $request['points'];
        $this->srid = str_replace("EPSG:","",$request['srs']);

        $this->db = new PgRoutingDBInterface($connection,$this->waysTable,$this->verticesTable,$this->srid);
        */
    }

    public function getRoute($requestParams, $configuration)
    {
        // TODO: Implement getRoute() method.
    }

    public function getWayPointsList() {
        $transformed = $this->db->getTransformedCoordinates();
        $waypoints_list = array_map(function($pt) {
            return ["coordinates" => [0 => $pt["x"], 1 => $pt["y"]]];
        },$transformed);


        return $waypoints_list;
    }

    public function getResponse(): array
    {
        /* create List of Coord in the Ewkt-Format */
        $points = array_map(function($wkt) { return ["wkt" => $wkt]; },$this->convertCoordToEwkt());
        /* match the listEWKT to prouting-wayVerticesTableName */
        // counter for creating serial node Id of added waypoint
        $maxId =  $this->db->getMaxId();

        foreach ($points as &$point) {

            $point["newid"] = ++$maxId;
            $this->db->adjustTemporaryTables($point['wkt'],$this->weighting);
        }


        /* create RoutingResult between Nodes */
        $pgRouteResult = $this->db->routeBetweenNodes(array_map(function($pt){ return $pt["newid"]; },$points), $this->weighting);

        $instruction = self::getInstructionAsArray($pgRouteResult);

        $totalDistance = self::getTotalDistance($pgRouteResult);

        $waypoints_list = $this->getWayPointsList();


        /* define PropertiesArray for setResponseData */
        $properties = array(
            'distance' => $totalDistance,
            'instructions' => $instruction,
            'waypoints' => $waypoints_list,
            'speed' => $this->speed
        );

        /* create a MultiLineGeoJson Result from $pgRouteResult */
        $pgRouteResultGeom = json_decode($this->db->getResultGeom(array_map(function($res) { return $res["geom"];}, $pgRouteResult)), true);

        $this->type = $pgRouteResultGeom["type"];

        $this->db->dropTempTables();


        $code = 200;
        $routeData = json_encode(array_merge($pgRouteResultGeom, $properties));
        $err = "successfully";



        # Create Curl-Response
        return array(
            'responseData' => $routeData,
            'responseCode' => $code,
            'curl_error' => $err
        );


    }


    /**
     * Returns all coordinates as Ewkt-arrayList
     * @return mixed
     */
    protected function convertCoordToEwkt()
    {
        $resultCoordPairs = array();
        foreach ($this->points as $point) {
            $lonLat = implode(" ", $point);
            $resultCoordPairs[] = 'POINT(' . $lonLat . ')';
        }
        return $resultCoordPairs;
    }

    protected static function getInstructionAsArray($pgRouteResult)
    {
        $instruction = null;
        foreach ($pgRouteResult as $pgRouteData) {

            $instruction[] = array(
                'name' => $pgRouteData['strname'],
                'distance' => $pgRouteData['sum_filter_distance'],
                'time' => $pgRouteData['time'], // in ms
                'sign' => $pgRouteData['sign'],
                'text' => $pgRouteData['sign'],
                'heading' => 'heading'
            );

        }
        return $instruction;
    }

    /**
     * @param $pgRouteResult
     * @return float|int|string
     */
    protected static function getTotalDistance($pgRouteResult)
    {
        $distance = array();

        foreach ($pgRouteResult as $pgRouteData) {
            if (isset($pgRouteData['distance'])) {
                $distance[] = $pgRouteData['distance'];
            }
        }

        $totaldistance = array_sum($distance);

        return $totaldistance;
    }

    public function processResponse($response)
    {

        $graphTimeFormat = "ms";

        # Coordinate Points
        $points = $response['coordinates'];

        # ResultGraphLength to meters
        $graphLength = $response['distance'];
        $vehicleSpeed = $response['speed'];
            // km = m/1000
        $graphLengthKm = ($graphLength / 1000);
            // t = s/v
        $timeToHour = $graphLengthKm / $vehicleSpeed;
            // convert to ms
        $graphTimeToMs = ($timeToHour * 60 * 60 * 1000);
        $graphTime = $graphTimeFormat === 'ms' ? $graphTimeToMs : self::getSpeedTimeToDateFormat($graphTimeToMs, $graphTimeFormat);



        # instructions

        $instructions = $response['instructions'];
        $instructions = $this->translateInstructions($instructions);
        $instructions = $this->addInstructionSymbols($instructions);

        $wayPointsResponse = $response['waypoints'];

        $graphLengthUnit = "meters";
        return $this->createFeatureInGeoJson($points,$graphLength,$graphLengthUnit,$graphTime,$graphTimeFormat,$instructions,$wayPointsResponse);

    }

    /**
     * @param string $graphTimeToMs
     * @param string $graphTimeFormat
     * @return false|string
     * @throws Exception
     */
    private static function getSpeedTimeToDateFormat($graphTimeToMs = 'MircoSec', $graphTimeFormat = 'H:i:s')
    {
        $time = $graphTimeToMs / 1000;
        $days = floor($time / (24 * 60 * 60));
        $hours = floor(($time - ($days * 24 * 60 * 60)) / (60 * 60));
        $minutes = floor(($time - ($days * 24 * 60 * 60) - ($hours * 60 * 60)) / 60);
        $seconds = ($time - ($days * 24 * 60 * 60) - ($hours * 60 * 60) - ($minutes * 60)) % 60;

        $dateTime = new DateTime();
        date_time_set($dateTime, $hours, $minutes, $seconds);

        return date_format($dateTime, $graphTimeFormat);
    }

    protected function addSpecificInstruction(array $nativeInstruction)
    {
        $on = $this->translator->trans('mb.routing.backend.sign.on');
        $text = $this->translator->trans('mb.routing.backend.sign.' . $nativeInstruction['text']);

        if ($nativeInstruction['name']) {
            $textStrname = $text . ' ' . $on . ' ' . $nativeInstruction['name'];
        } else {
            $textStrname = $text;
        }

        return array(
            'text' => $textStrname,
            'leg' => null
        );

    }
}
