<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOM\CoreBundle\Component\GeoConverterComponent;

/**
 * Class Feature
 *
 * @package   Mapbender\CoreBundle\Entity
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @ORM\Entity
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

    protected $srid;

    protected $attributes;


    /**
     * @var GeoConverterComponent
     */
    public static $geoConverter;

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
     * @param string|array $data
     */
    public function __construct($data){
        if(!self::$geoConverter){
            self::$geoConverter = new GeoConverterComponent();
        }
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
                     'geometry'   => $this->getGeom());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $data = $this->toGeoJson();
        return json_encode(self::$geoConverter->wktToGeoJson($data));
    }
}