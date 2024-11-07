<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OsrmDriver extends RoutingDriver {

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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getRoute($requestParams, $configuration)
    {
        $this->locale = $configuration['locale'];
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
        $routingInstructions = [];

        if (!empty($osrmConfig['steps']) && isset($response['routes'][0]['legs'][0]['steps'])) {
            $routingInstructions = $this->addInstructionSymbols($this->translateInstructions($response['routes'][0]['legs'][0]['steps']));
        }

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
            'routingInstructions' => $routingInstructions,
        ];
    }

    private function buildQuery($requestParams, $config): string
    {
        $service = ($config['service']) ?: 'route';
        $version = ($config['version']) ?: 'v1';
        $profile = ($requestParams['vehicle']) ?: 'car';
        $url = trim($config['url'], '/') . '/' . $service . '/' . $version . '/' . $profile . '/';
        $coordinates = [];

        foreach ($requestParams['points'] as $point) {
            $coordinates[] = $point[0] . ',' .  $point[1];
        }

        $coordinateString = implode(';', $coordinates);
        $steps = ($config['steps']) ?: false;
        $queryParams = [
            'geometries' => 'geojson',
            'steps' => $steps,
            # 'alternatives' => ($config['alternatives']) ?: false,
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
        $mod = isset($instruction['maneuver']['modifier']) ? $instruction['maneuver']['modifier'] : 'default';
        return $mod;
    }

    protected function getInstructionText($instruction) {
        $translatedInstructions = $this->translate($instruction);
        if (!$translatedInstructions) {
            return $instruction['maneuver']['type'].", ".$this->getInstructionSign($instruction)." - ".$instruction['name'];
        }
        return $translatedInstructions;
    }

    protected function translate($instruction)
    {
        $path = realpath(__DIR__ . '/../../Resources/translations/osrm') . '/';
        $filename = 'osrm.' . $this->locale . '.json';
        if (!file_exists($path . $filename)) {
            $filename = 'osrm.en.json';
        }
        $translations = file_get_contents($path . $filename);
        $translations = json_decode($translations, true);
        $translations = $translations['v5'];
        $type = $instruction['maneuver']['type'];
        $modifier = (isset($instruction['maneuver']['modifier'])) ? $instruction['maneuver']['modifier'] : false;
        $streetName = $instruction['name'];

        if (empty($streetName) && isset($instruction['ref'])) {
            $streetName = $instruction['ref'];
        }

        if (empty($streetName) && isset($instruction['destinations'])) {
            $streetName = $instruction['destinations'];
        }

        if ($type == 'depart') {
            $direction = $this->getDirection($instruction);
            $direction = $translations['constants']['direction'][$direction];
            return str_replace(['{direction}', '{way_name}'], [$direction, $streetName], $translations[$type]['default']['name']);
        }

        if ($type == 'rotary' || $type == 'roundabout') {
            return str_replace('{way_name}', $streetName, $translations[$type]['default']['default']['name']);
        }

        if (isset($translations[$type][$modifier])) {
            return str_replace('{way_name}', $streetName, $translations[$type][$modifier]['name']);
        } elseif (isset($translations[$type]['default'])) {
            $modifier = $translations['constants']['modifier'][$modifier];
            return str_replace(['{way_name}', '{modifier}'], [$streetName, $modifier], $translations[$type]['default']['name']);
        } else {
            return false;
        }
    }

    protected function getDirection($instruction) {
        $bearing = floatval($instruction['maneuver']['bearing_after']);

        if ($bearing >= 337.5 && $bearing < 22.5) {
            return 'north';
        } elseif ($bearing >= 22.5 && $bearing < 67.5) {
            return 'northeast';
        } elseif ($bearing >= 67.5 && $bearing < 112.5) {
            return 'east';
        } elseif ($bearing >= 112.5 && $bearing < 157.5) {
            return 'southeast';
        } elseif ($bearing >= 157.5 && $bearing < 202.5) {
            return 'south';
        } elseif ($bearing >= 202.5 && $bearing < 247.5) {
            return 'southwest';
        } elseif ($bearing >= 247.5 && $bearing < 292.5) {
            return 'west';
        } elseif ($bearing >= 292.5 && $bearing < 337.5) {
            return 'northwest';
        } else {
            return false;
        }
    }
}
