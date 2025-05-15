<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Mapbender\Component\Transport\ConnectionErrorException;
use Mapbender\Component\Transport\HttpTransportInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OsrmDriver extends RoutingDriver
{
    const SRID = '4326';

    protected string $timeField = 'duration';

    protected string $timeScale = 's';

    protected string $locale;

    protected HttpTransportInterface $httpTransport;

    protected TranslatorInterface $translator;

    public function __construct(HttpTransportInterface $httpTransport, TranslatorInterface $translator)
    {
        $this->httpTransport = $httpTransport;
        $this->translator = $translator;
        parent::__construct($translator);
    }

    /**
     * @throws ConnectionErrorException
     */
    public function getRoute($requestParams, $configuration): array
    {
        $this->locale = $configuration['locale'];
        $osrmConfig = $configuration['routingConfig']['osrm'];
        $query = $this->buildQuery($requestParams, $osrmConfig);
        $response = $this->httpTransport->getUrl($query);
        $response = json_decode($response->getContent(), true);
        return $this->processResponse($response, $configuration);
    }

    private function buildQuery($requestParams, $config): string
    {
        $service = ($config['service']) ?: 'route';
        $version = ($config['version']) ?: 'v1';
        $profile = (!empty($requestParams['vehicle'])) ? $requestParams['vehicle'] : 'car';

        $url = str_replace('%profile', $profile, trim($config['url'], '/'));
        $url .=  '/' . $service . '/' . $version . '/driving/';
        $coordinates = [];

        foreach ($requestParams['points'] as $point) {
            $coordinates[] = $point[0] . ',' .  $point[1];
        }

        $coordinateString = implode(';', $coordinates);
        $steps = ($config['steps']) ?: false;
        $queryParams = [
            'geometries' => 'geojson',
            'overview' => 'full',
            'steps' => $steps,
            # 'alternatives' => ($config['alternatives']) ?: false,
        ];

        return $url . $coordinateString . '?' . http_build_query($queryParams);
    }

    public function processResponse($response, $configuration): array
    {
        $osrmConfig = $configuration['routingConfig']['osrm'];
        $coordinates = $response['routes'][0]['geometry']['coordinates'];
        $type = $response['routes'][0]['geometry']['type'];
        $start = $this->getStartAddress($response);
        $destination = $this->getDestinationAddress($response);
        $distance = $response['routes'][0]['distance'];
        $time = $response['routes'][0]['duration'];
        $infoText = $configuration['infoText'];
        $routingInstructions = [];

        if (!empty($osrmConfig['steps']) && isset($response['routes'][0]['legs'][0]['steps'])) {
            $routingInstructions = $this->getRoutingInstructions($response['routes'][0]['legs'][0]['steps']);
        }

        return [
            'featureCollection' => $this->createFeatureCollection($coordinates, $type, self::SRID),
            'routeInfo' => $this->getRouteInfo($start, $destination, $distance, $time, $infoText),
            'routingInstructions' => $routingInstructions,
        ];
    }

    protected function getInstructionSignMapping(): array
    {
        return [
            'uturn'	=> static::INSTR_UTURN_RIGHT,
            'sharp right' => static::INSTR_RIGHT3,
            'right'	=> static::INSTR_RIGHT2,
            'slight right' => static::INSTR_RIGHT1,
            'straight' => static::INSTR_CONTINUE,
            'slight left' => static::INSTR_LEFT1,
            'left' => static::INSTR_LEFT2,
            'sharp left' => static::INSTR_LEFT1,
        ];
    }

    public function getInstructionSign($instruction): string
    {
        return $instruction['maneuver']['modifier'] ?? 'default';
    }

    protected function getInstructionText($instruction): string
    {
        $translatedInstructions = $this->translateOsrmInstruction($instruction);
        if (!$translatedInstructions) {
            return $instruction['maneuver']['type'] . ', ' . $this->getInstructionSign($instruction) . ' - ' . $instruction['name'];
        }
        return $translatedInstructions;
    }

    protected function translateOsrmInstruction($instruction): array|bool|string
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

    protected function getDirection($instruction): bool|string
    {
        $bearing = floatval($instruction['maneuver']['bearing_after']);

        if (($bearing >= 337.5 && $bearing <= 360) || ($bearing >= 0 && $bearing < 22.5)) {
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

    protected function getStartAddress($response)
    {
        if (!empty($response['waypoints'][0]['name'])) {
            return $response['waypoints'][0]['name'];
        }
        $startAddress = 'Start';
        if (!empty($response['routes'][0]['legs'][0]['summary'])) {
            $startAddress = explode(',', $response['routes'][0]['legs'][0]['summary']);
            $startAddress = trim($startAddress[0]);
        }
        return $startAddress;
    }

    protected function getDestinationAddress($response)
    {
        $destinationIndex = count($response['waypoints']) - 1;
        if (!empty($response['waypoints'][$destinationIndex]['name'])) {
            return $response['waypoints'][$destinationIndex]['name'];
        }
        $destinationAddress = 'Destination';
        if (!empty($response['routes'][0]['legs'][0]['summary'])) {
            $destinationAddress = explode(',', $response['routes'][0]['legs'][0]['summary']);
            $destinationAddress = (count($destinationAddress) > 1) ? trim($destinationAddress[1]) : $destinationAddress;
        }
        return $destinationAddress;
    }
}
