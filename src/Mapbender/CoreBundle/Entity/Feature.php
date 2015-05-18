<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Feature
 *
 * @package   Mapbender\CoreBundle\Entity
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 */
class Feature
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(name="GEOM", type="text", nullable=true)
     */
    protected $geom;

    /**
     * @var
     */
    protected $srid;

    /**
     * @var
     */
    protected $attributes;

    /** @var string */
    private $uniqueIdField;

    /** @var string */
    private $geomField;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $geom
     * @return $this
     */
    public function setGeom($geom)
    {
        $this->geom = $geom;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGeom()
    {
        return $this->geom;
    }

    /**
     * @return mixed
     */
    public function getSrid()
    {
        return $this->srid;
    }

    /**
     * @param mixed $srid
     */
    public function setSrid($srid)
    {
        $this->srid = intval($srid);
    }

    /**
     *  has SRID?
     */
    public function hasSrid()
    {
        return !!$this->srid;
    }

    /**
     * Get attributes (parameters)
     *
     * @return mixed
     */
    public function getAttributes()
    {
        $this->attributes[$this->uniqueIdField] = $this->getId();
        return $this->attributes;
    }

    /**
     * @param mixed $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param mixed $args JSON or array(
     * @param int $srid
     * @param string $uniqueIdField ID field name
     * @param string $geomField GEOM field name
     */
    public function __construct($args = null, $srid = null, $uniqueIdField = 'id', $geomField = "geom")
    {
        // decode JSON
        if (is_string($args)) {
            $args = json_decode($args, true);
            if (isset($args["geometry"])) {
                $args["geom"] = \geoPHP::load($args["geometry"], 'json')->out('wkt');
            }
        }
        
        $this->setSrid($srid);

        // Is JSON feature array?
        if (is_array($args) && isset($args["geometry"]) && isset($args['properties'])) {
            $properties             = $args["properties"];
            $geom                   = $args["geometry"];
            $properties[$geomField] = $geom;

            if (isset($args['id'])) {
                $properties[$uniqueIdField] = $args['id'];
            }

            if (isset($args['srid'])) {
               $this->setSrid($args['srid']);
            }

            $args = $properties;
        }

        // set GEOM
        if (isset($args[$geomField])) {
            $this->setGeom($args[$geomField]);
            unset($args[$geomField]);
        }

        // set ID
        if (isset($args[$uniqueIdField])) {
            $this->setId($args[$uniqueIdField]);
            unset($args[$uniqueIdField]);
        }

        // set attributes
        $this->setAttributes($args);


        $this->uniqueIdField = $uniqueIdField;
        $this->geomField = $geomField;

    }

    /**
     * Get GeoJSON
     *
     * @param bool $decodeGeometry
     * @return array in GeoJSON format
     * @throws \exception
     */
    public function toGeoJson( $decodeGeometry = true)
    {
        $wkt = \geoPHP::load($this->getGeom(), 'wkt')->out('json');
        if($decodeGeometry){
            $wkt = json_decode($wkt, true);
        }

        return array('type'       => 'Feature',
                     'properties' => $this->getAttributes(),
                     'geometry'   => $wkt,
                     'id'         => $this->getId(),
                     'srid'       => $this->getSrid());
    }

    /**
     * Return GeoJSON string
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toGeoJson());
    }

    /**
     * Return array
     *
     * @return mixed
     */
    public function toArray()
    {
        $data = $this->getAttributes();

        if ($this->hasGeom()) {
            //$wkb = \geoPHP::load($feature->getGeom(), 'wkt')->out('wkb');
            if ($this->getSrid()) {
                $data[$this->geomField] = "SRID=" . $this->getSrid() . ";" . $this->getGeom();
            } else {
                $data[$this->geomField] = $this->srid . ";" . $this->getGeom();
            }
        }

        if (!$this->hasId()) {
            unset($data[$this->uniqueIdField]);
        }else{
            $data[$this->uniqueIdField] = $this->getId();
        }

        return $data;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Is id set
     *
     * @return bool
     */
    public function hasId(){
        return !is_null($this->id);
    }

    /**
     * Has geom data
     *
     * @return bool
     */
    public function hasGeom(){
        return !is_null($this->geom);
    }
}