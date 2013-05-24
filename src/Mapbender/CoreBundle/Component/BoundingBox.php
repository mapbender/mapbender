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
     * @var srs Spatial reference system
     * 
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $srs;

    /**
     * @var minx Minimum X of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $minx;

    /**
     * @var miny Minimum Y of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $miny;

    /**
     * @var maxx Maximum X of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $maxx;

    /**
     * @var maxy Maximum Y of the Bounding Box
     * ORM\Column(type="float", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $maxy;

    /**
     * Creates a BoundingBox from parameters
     * 
     * @param array $parameters
     */
    public static function create(array $parameters)
    {
        try
        {
            return new BoundingBox(
                            isset($parameters["srs"]) ? $parameters["srs"] : null,
                            isset($parameters["minx"]) ? $parameters["minx"] : null,
                            isset($parameters["miny"]) ? $parameters["miny"] : null,
                            isset($parameters["maxx"]) ? $parameters["maxx"] : null,
                            isset($parameters["maxy"]) ? $parameters["maxy"] : null
            );
        } catch(\Exception $e)
        {
            return null;
        }
    }

    /**
     * Creates a BoundingBox
     * 
     * @param type $srs srs
     * @param type $minx minx
     * @param type $miny miny
     * @param type $maxx maxx
     * @param type $maxy maxy
     */
    public function __construct($srs = null, $minx = null, $miny = null,
            $maxx = null, $maxy = null)
    {
        $this->srs = $srs;
        $this->minx = $minx;
        $this->miny = $miny;
        $this->maxx = $maxx;
        $this->maxy = $maxy;
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

}