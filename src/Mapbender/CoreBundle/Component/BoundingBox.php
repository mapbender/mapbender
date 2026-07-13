<?php
namespace Mapbender\CoreBundle\Component;

/**
 * @author Paul Schmidt
 */
class BoundingBox
{
    /**
     * Creates a BoundingBox from parameters
     *
     * @param array $parameters
     * @return BoundingBox
     */
    public static function create(array $parameters): BoundingBox
    {
        return new BoundingBox(
            $parameters["srs"] ?? null,
            $parameters["minx"] ?? null,
            $parameters["miny"] ?? null,
            $parameters["maxx"] ?? null,
            $parameters["maxy"] ?? null
        );
    }

    /**
     * Creates a BoundingBox
     *
     * @param string $srs  srs
     * @param float $minx minx
     * @param float $miny miny
     * @param float $maxx maxx
     * @param float $maxy maxy
     */
    public function __construct(public $srs = null, public $minx = null, public $miny = null, public $maxx = null, public $maxy = null)
    {
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
    public function setSrs($value): static
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
    public function setMinx($value): static
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
    public function setMiny($value): static
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
    public function setMaxx($value): static
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
    public function setMaxy($value): static
    {
        $this->maxy = $value;
        return $this;
    }

    /**
     * Get object as array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            "srs" => $this->srs,
            "minx" => $this->minx,
            "miny" => $this->miny,
            "maxx" => $this->maxx,
            "maxy" => $this->maxy
        ];
    }

    /**
     * The entity handlers like to call this, for database storage maybe
     * @return float[]
     */
    public function toCoordsArray(): array
    {
        return [
            floatval($this->getMinx()),
            floatval($this->getMiny()),
            floatval($this->getMaxx()),
            floatval($this->getMaxy())
        ];
    }

}
