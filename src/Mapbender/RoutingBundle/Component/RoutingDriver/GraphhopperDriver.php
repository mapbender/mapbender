<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GraphhopperDriver extends RoutingDriver {


    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $elevation;

    /**
     * @var string
     */
    protected $weighting;

    /**
     * @var string
     */
    protected $instructions;

    /**
     * @var string
     */
    protected $points_encoded;

    /**
     * @var string
     */
    protected $responseType;

    /**
     * @var string
     */
    protected $optimize;

    /**
     * @var string
     */
    protected $vehicle;

    /**
     * @var array
     */
    protected $points;

    protected $srid = "4326";
    protected $type = "LineString";

    protected HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        /*
        parent::__construct($locale,$translator);

        $this->responseType = "json";

        # params from RoutingElementAdminType
        $this->url = $config['url'];
        $this->key =  $config['key'];
        $this->elevation = $config['elevation'];
        $this->weighting = $config['weighting'];

        $this->optimize = $config['optimize'];
        $this->instructions = $config['instructions'];

        # params from Frontend
        $this->vehicle = $request["vehicle"];
        $this->points = $request['points'];
        */
    }

    public function getRoute($requestParams, $configuration)
    {
        // TODO: Implement getRoute() method.
    }

    public function getResponse() : array {
        # create URL-String from config & routing params
        $query = $this->createQuery();
        return self::getCurlResponse($query);
    }

    /**
     * Create $queryUrl for Curl-Request
     *
     * @return string
     */
    private function createQuery() {
        $points_query = implode(array_map(function($point) {
            return "point={$point[1]},{$point[0]}";
        },$this->points),"&");

        $details = "street_name";
        $points_encoded = "false";


       $query = array ("type" => $this->responseType, "locale" => $this->locale, "vehicle" => $this->vehicle,
           "weighting" => $this->weighting, "elevation" => $this->elevation, "instructions" => $this->instructions,
           "ch.disable" => $this->optimize, "points_encoded" => $points_encoded, "details" => $details);
       if ($this->key) {
           $query['key'] = $this->key;
       }
       $queryUrl = $this->url.$points_query."&".http_build_query($query);
       return $queryUrl;
    }

    public function processResponse($response) {

        $instructions = null;
        $wayPointsResponse = null;

        $graphTimeFormat = 'ms';

        $path = $response['paths'][0];

        # Coordinate Points
        $points = $path['points']['coordinates'];

        # ResultGraphLength to meters
        $graphLength = $path['distance'];
        $graphLengthUnit = 'Meters';

        $graphTime = isset($path['time']) ? $path['time'] : null;

        # instructions
        if (isset($path['instructions'])) {
            $instructions = $this->addInstructionSymbols($this->translateInstructions($path['instructions']));
        }

        $waypointsInput = $path['snapped_waypoints']['coordinates'];
        $waypointsList = [];

        foreach ($waypointsInput as $coordinates) {
            $waypointsList[] = array(
                'srid' => 'EPSG:' . $this->srid,
                'coordinates' => $coordinates
            );
        }


        return $this->createFeatureInGeoJson($points,$graphLength,$graphLengthUnit,$graphTime,$graphTimeFormat,$instructions,$waypointsList);

    }
}
