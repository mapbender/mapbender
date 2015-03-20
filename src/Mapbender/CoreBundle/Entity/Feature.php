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
        $this->srid = $srid;
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
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

        // set GEOM
        if (isset($args[$geomField])) {
            $this->setGeom($args[$geomField]);
            unset($args[$geomField]);
        }

        if (isset($args["geom"])) {
            $this->setGeom($args["geom"]);
            unset($args["geom"]);
        }

        // set ID
        if (isset($args[$uniqueIdField])) {
            $this->setId($args[$uniqueIdField]);
            unset($args[$uniqueIdField]);
        }

        if (isset($args['id'])) {
            $this->setId($args['id']);
            unset($args['id']);
        }

        // set attributes
        $this->setAttributes($args);
        $this->setSrid($srid);
    }

    /**
     * Get GeoJSON
     *
     * @return array in GeoJSON format
     */
    public function toGeoJson()
    {
        return array('type'       => 'Feature',
                     'properties' => $this->getAttributes(),
                     'geometry'   => \geoPHP::load($this->getGeom(), 'wkt')->out('json'),
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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function hasId(){
        return !is_null($this->id);
    }

    public function hasGeom(){
        return !is_null($this->geom);
    }
}