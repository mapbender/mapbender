<?php
namespace Mapbender\WmsBundle\Entity;


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
     * Gets srs
     * 
     * @return string
     */
    public function getSrs() {
        return $this->srs;
    }
    /**
     * Sets srs
     * @param string $value 
     */
    public function setSrs($value) {
        $this->srs = $value;
    }
    
    /**
     * Gets minx
     * 
     * @return float
     */
    public function getMinx() {
        return $this->minx;
    }
    /**
     * Sets minx
     * @param float $value 
     */
    public function setMinx($value) {
        $this->minx = $value;
    }
    
    /**
     * Gets miny
     * 
     * @return float
     */
    public function getMiny() {
        return $this->miny;
    }
    /**
     * Sets miny
     * @param float $value 
     */
    public function setMiny($value) {
        $this->miny = $value;
    }
    
    /**
     * Gets maxx
     * 
     * @return float
     */
    public function getMaxx() {
        return $this->maxx;
    }
    /**
     * Sets maxx
     * @param float $value 
     */
    public function setMiny($value) {
        $this->maxx = $value;
    }
    
    /**
     * Gets maxy
     * 
     * @return float
     */
    public function getMaxy() {
        return $this->maxy;
    }
    /**
     * Sets maxy
     * @param float $value 
     */
    public function setMiny($value) {
        $this->maxy = $value;
    }
    
    /**
     * Gets object as array
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