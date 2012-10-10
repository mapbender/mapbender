<?php
namespace Mapbender\CoreBundle\Component;


/**
 * BoundingBox class.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class BoundingBox {
    /**
     * ORM\Column(type="string", nullable=false)
     */
    protected $srs;
    /**
     * ORM\Column(type="float", nullable=false)
     */
    protected $minx;
    /**
     * ORM\Column(type="float", nullable=false)
     */
    protected $miny;
    /**
     * ORM\Column(type="float", nullable=false)
     */
    protected $maxx;
    /**
     * ORM\Column(type="float", nullable=false)
     */
    protected $maxy;
    
    /**
     * Creates a BoundingBox object from parameters
     * @param array $parameters
     */
    public static function create(array $parameters) {
        try {
            $bbox = new BoundingBox();
            $bbox->setSrs($parameters["srs"]);
            $bbox->setMinx($parameters["minx"]);
            $bbox->setMaxx($parameters["maxx"]);
            $bbox->setMiny($parameters["miny"]);
            $bbox->setMaxy($parameters["maxy"]);
            return $bbox;
        } catch(\Exception $e){
            return null;
        }
    }
    
    /**
     * Get srs
     * 
     * @return string
     */
    public function getSrs() {
        return $this->srs;
    }
    /**
     * Set srs
     * @param string $value 
     */
    public function setSrs($value) {
        $this->srs = $value;
    }
    
    /**
     * Get minx
     * 
     * @return float
     */
    public function getMinx() {
        return $this->minx;
    }
    /**
     * Set minx
     * @param float $value 
     */
    public function setMinx($value) {
        $this->minx = $value;
    }
    
    /**
     * Get miny
     * 
     * @return float
     */
    public function getMiny() {
        return $this->miny;
    }
    /**
     * Set miny
     * @param float $value 
     */
    public function setMiny($value) {
        $this->miny = $value;
    }
    
    /**
     * Get maxx
     * 
     * @return float
     */
    public function getMaxx() {
        return $this->maxx;
    }
    /**
     * Set maxx
     * @param float $value 
     */
    public function setMaxx($value) {
        $this->maxx = $value;
    }
    
    /**
     * Get maxy
     * 
     * @return float
     */
    public function getMaxy() {
        return $this->maxy;
    }
    /**
     * Set maxy
     * @param float $value 
     */
    public function setMaxy($value) {
        $this->maxy = $value;
    }
    
    /**
     * Get object as array
     * 
     * @return array
     */
    public function toArray() {
        return array (
            "srs" => $this->srs,
            "minx" => $this->minx,
            "maxx" => $this->maxx,
            "miny" => $this->miny,
            "maxy" => $this->maxy
            );
    }
    
}