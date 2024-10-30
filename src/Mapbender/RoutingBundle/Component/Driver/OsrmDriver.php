<?php

namespace Mapbender\RoutingBundle\Component\Driver;

use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OsrmDriver extends RoutingDriver {


    /**
     * @var null|string
     */
    public $url;

    /**
     * @var null|string
     */
    public $service;

    /**
     * @var null|string
     */
    public $locale;

    /**
     * @var null|string
     */
    public $version;

    /**
     * @var string
     */
    public $alternatives;

    /**
     * @var null|string
     */
    public $instructions;

    /**
     * @var string
     */
    public $annotations;

    /**
     * @var string
     */
    public $geometries;

    /**
     * @var string
     */
    public $overview;

    /**
     * @var string
     */
    public $vehicle;

    /**
     * @var bool
     */
    public $continueStraight;

    /**
     * @var array
     */
    public $points = array();

    protected $srid = "4326";
    protected $type = "LineString";

    protected $timeField = "duration";
    protected $timeScale = "s";

    protected HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /*
    public function __construct(array $config, array $request, string $locale, TranslatorInterface $translator)
    {
        parent::__construct($locale,$translator);

        # params from RoutingElementAdminType
        $this->url = $config['url'];
        $this->service = $config['service'];
        $this->version = $config['version'];
        $this->alternatives = $config['alternatives'];
        $this->instructions = $config['steps'];
        $this->overview = $config['overview'];
        $this->annotations = $config['annotations'];

        # params from Frontend
        $this->vehicle = $request['vehicle'];
        $this->points = $request['points'];

        // Denormalize Vehicle and replace Profile-Pattern of URL-String
        if (strpos(strtolower($this->url), "{profile}") !== false) {
            $this->url = str_ireplace("{profile}",$this->vehicle, $this->url);
        }

    }
    */

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getRoute($requestParams, $configuration)
    {
        $osrmConfig = $configuration['routingConfig']['osrm'];
        $query = $this->buildQuery($requestParams, $osrmConfig);
        $response = $this->httpClient->request('GET', $query);
        $response = $response->toArray(false);

        $formatDistance = function ($distance) {
            if (floatval($distance) >= 1000) {
                return round((floatval($distance) / 1000.0), 2) . ' KM';
            }
            return $distance . 'm';
        };

        $formatTime = function ($time) {
            $time = gmdate('H:i', $time);
            if (substr($time, 0, 2) == '00') {
                $minutes = substr($time, 3, strlen($time));
                return (substr($minutes, 0, 1) == '0') ? substr($minutes, 1, 1) . ' Min' : $minutes . ' Min';
            }
            return $time . ' Std.';
        };

        $destinationIndex = count($response['waypoints']) - 1;
        $search = ['{start}', '{destination}', '{length}', '{time}'];
        $replace = [
            $response['waypoints'][0]['name'],
            $response['waypoints'][$destinationIndex]['name'],
            $formatDistance($response['routes'][0]['distance']),
            $formatTime($response['routes'][0]['duration']),
        ];

        return [
            'featureCollection' => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => $response['routes'][0]['geometry']['type'],
                    'coordinates' => $response['routes'][0]['geometry']['coordinates'],
                ],
                'properties' => [
                    'srs' => 'EPSG:4326',
                ],
            ],
            'routeInfo' => str_replace($search, $replace, $configuration['infoText']),
        ];
    }

    private function buildQuery($requestParams, $config): string
    {
        $service = ($config['service']) ? $config['service'] : 'route';
        $version = ($config['version']) ? $config['version'] : 'v1';
        $profile = ($requestParams['vehicle']) ? $requestParams['vehicle'] : 'car';
        $url = trim($config['url'], '/') . '/' . $service . '/' . $version . '/' . $profile . '/';
        $coordinates = [];

        foreach ($requestParams['points'] as $point) {
            $coordinates[] = $point[0] . ',' .  $point[1];
        }

        $coordinateString = implode(';', $coordinates);
        $queryParams = [
            #'alternatives' => $this->alternatives,
            #'steps' => $this->instructions,
            'geometries' => 'geojson',
            #'overview' => $this->overview,
            #'annotations' => $this->annotations,
            #'continue_straight' => 'default',
        ];

        return $url . $coordinateString . '?' . http_build_query($queryParams);
    }

    public function getResponse() : array {
        # create URL-String from config & routing params
        $query = $this->createQuery();
        return self::getCurlResponse($query);
    }

    public function processResponse($response) {

        $instructions = null;
        $wayPointsResponse = null;

        $path = $response['routes'][0];
        # SRID to EPSG-Code
        $srid = '4326';

        # Coordinate Points
        $points = $path['geometry']['coordinates'];


        # ResultGraphLength to meters
        $graphLength = $path['distance'];
        $graphLengthUnit = 'Meters';

        $graphTimeFormat = 'ms';
        $graphTime = $path['duration'] * 1000; // seconds to miliseconds

        if (isset($path['legs'][0]["steps"])) {
            $instructions = $this->addInstructionSymbols($this->translateInstructions($path['legs'][0]['steps']));
        }

        $waypointsList = [];

        $waypointsInput = $response['waypoints'];

        foreach ($waypointsInput as $value) {
            $waypointsName = $value['name'];
            $waypointsCoord = $value['location'];
            $waypoints = array(
                'name' => $waypointsName,
                'srid' => 'EPSG:' . $srid,
                'coordinates' => $waypointsCoord
            );
            $waypointsList[] = $waypoints;
        }

        return $this->createFeatureInGeoJson($points,$graphLength,$graphLengthUnit,$graphTime,$graphTimeFormat,$instructions,$waypointsList);

    }

    /**
     * @return array
     */
    protected function getInstructionSignMapping()
    {
        return array(
            "uturn"	=> static::INSTR_UTURN_RIGHT,
            "sharp right" => static::INSTR_RIGHT3,
            "right"	=> static::INSTR_RIGHT2,
            "slight right" => static::INSTR_RIGHT1,
            "straight" => static::INSTR_CONTINUE,
            "slight left" => static::INSTR_LEFT1,
            "left" => static::INSTR_LEFT2,
            "sharp left" => static::INSTR_LEFT1,
        );

    }


    public function getInstructionSign($instruction) {
        $mod = isset($instruction['maneuver']['modifier']) ? $instruction['maneuver']['modifier'] : "straight";
        return $mod;
    }

    protected function getInstructionText($instruction) {
        return $instruction['maneuver']['type'].", ".$this->getInstructionSign($instruction)." - ".$instruction['name'];
    }

}
