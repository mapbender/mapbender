<?php

namespace Mapbender\RoutingBundle\Component\Driver;

use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\Translation\TranslatorInterface;

class OSRMDriver extends RoutingDriver {


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



    public function getResponse() : array {
        # create URL-String from config & routing params
        $query = $this->createQuery();
        return self::getCurlResponse($query);
    }

    private function createQuery()
    {

        $continueStraight = 'default';
        $geometries = "geojson";

        $points = array();
        foreach ($this->points as $pointPair) {
            $points[] = $pointPair[0] . ',' .  $pointPair[1];
        }
        $graphPair = implode(';',$points);

        $query = array ("alternatives" => $this->alternatives, "steps" => $this->instructions, "geometries" => $geometries,
            "overview" => $this->overview, "annotations" => $this->annotations, "continue_straight" => $continueStraight);

        #setQueryDataUrl
        # GET /{service}/{version}/{profile}/{coordinates}[.{format}]?option=value&option=value
        # GET /route/v1/{profile}/{coordinates}?alternatives={true|false|number}&steps={true|false}&geometries={polyline|polyline6|geojson}&overview={full|simplified|false}&annotations={true|false}

        $queryUrl = rtrim($this->url,"/").'/'.$this->service.'/'.$this->version.'/'.$this->vehicle.'/'.$graphPair.'?'.http_build_query($query);
        return $queryUrl;

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
