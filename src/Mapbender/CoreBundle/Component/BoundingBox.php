<?php
namespace Mapbender\CoreBundle\Component;

/**
 * BoundingBox class.
 *
 * @author Paul Schmidt
 */
class BoundingBox
{
    /**
     * @var string srs Spatial reference system
     *
     * ORM\Column(type="string", nullable=false)
     */
    public $srs;

    /**
     * @var float minx Minimum X of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    public $minx;

    /**
     * @var float miny Minimum Y of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    public $miny;

    /**
     * @var float maxx Maximum X of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    public $maxx;

    /**
     * @var float maxy Maximum Y of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    public $maxy;

    /**
     * Creates a BoundingBox from parameters
     *
     * @param array $parameters
     * @return BoundingBox
     */
    public static function create(array $parameters)
    {
        return new BoundingBox(
            isset($parameters["srs"]) ? $parameters["srs"] : null,
            isset($parameters["minx"]) ? $parameters["minx"] : null,
            isset($parameters["miny"]) ? $parameters["miny"] : null,
            isset($parameters["maxx"]) ? $parameters["maxx"] : null,
            isset($parameters["maxy"]) ? $parameters["maxy"] : null
        );
    }

    /**
     * Creates a BoundingBox
     * 
     * @param string $srs  srs
     * @param float $minX minx
     * @param float $minY miny
     * @param float $maxX maxx
     * @param float $maxY maxy
     */
    public function __construct(
        $srs = null, $minX = null, $minY = null,
        $maxX = null, $maxY = null)
    {
        $this->srs = $srs;
        $this->minx = $minX;
        $this->miny = $minY;
        $this->maxx = $maxX;
        $this->maxy = $maxY;
    }

    /**
     * Get srs
     * 
     * @return string
     */
    public function getSrs()
    {
        return $this->srs;
    }

    /**
     * Set srs
     * @param string $value 
     * @return BoundingBox
     */
    public function setSrs($value)
    {
        $this->srs = $value;
        return $this;
    }

    /**
     * Get minx
     * 
     * @return float
     */
    public function getMinx()
    {
        return $this->minx;
    }

    /**
     * Set minx
     * @param float $value 
     * @return BoundingBox
     */
    public function setMinx($value)
    {
        $this->minx = $value;
        return $this;
    }

    /**
     * Get miny
     * 
     * @return float
     */
    public function getMiny()
    {
        return $this->miny;
    }

    /**
     * Set miny
     * @param float $value
     * @return BoundingBox
     */
    public function setMiny($value)
    {
        $this->miny = $value;
        return $this;
    }

    /**
     * Get maxx
     * 
     * @return float
     */
    public function getMaxx()
    {
        return $this->maxx;
    }

    /**
     * Set maxx
     * @param float $value 
     * @return BoundingBox
     */
    public function setMaxx($value)
    {
        $this->maxx = $value;
        return $this;
    }

    /**
     * Get maxy
     * 
     * @return float
     */
    public function getMaxy()
    {
        return $this->maxy;
    }

    /**
     * Set maxy
     * @param float $value 
     * @return BoundingBox
     */
    public function setMaxy($value)
    {
        $this->maxy = $value;
        return $this;
    }

    /**
     * Get object as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            "srs" => $this->srs,
            "minx" => $this->minx,
            "miny" => $this->miny,
            "maxx" => $this->maxx,
            "maxy" => $this->maxy
        );
    }

    /**
     * The entity handlers like to call this, for database storage maybe
     * @return float[]
     */
    public function toCoordsArray()
    {
        return array(
            floatval($this->getMinx()),
            floatval($this->getMiny()),
            floatval($this->getMaxx()),
            floatval($this->getMaxy())
        );
    }

}