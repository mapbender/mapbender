<?php
namespace Mapbender\WmtsBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;


/**
 * TileMatrix class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class TileMatrix {
    
    protected $identifier;
    
    protected $scaledenominator;
    
    protected $topleftcorner;
    
    protected $tilewidth;
    
    protected $tileheight;
    
    protected $matrixwidth;
    
    protected $matrixheight;
    
    public function __construct($tilematrix=null){
        if($tilematrix!=null && is_array($tilematrix)){
            $this->setIdentifier($tilematrix["identifier"]);
            $this->setScaledenominator($tilematrix["scaledenominator"]);
            $this->setTopleftcorner($tilematrix["topleftcorner"]);
            $this->setTilewidth($tilematrix["tilewidth"]);
            $this->setTileheight($tilematrix["tileheight"]);
            $this->setMatrixwidth($tilematrix["matrixwidth"]);
            $this->setMatrixheight($tilematrix["matrixheight"]);
        }
    }
    public function getIdentifier() {
        return $this->identifier;
    }
    
    public function setIdentifier($value) {
        $this->identifier = $value;
    }
    
    public function getScaledenominator() {
        return $this->scaledenominator;
    }
    
    public function setScaledenominator($value) {
        $this->scaledenominator = $value;
    }
    
    public function getTopleftcorner() {
        return $this->topleftcorner;
    }
    
    public function setTopleftcorner($value) {
        $this->topleftcorner = $value;
    }
    
    public function getTilewidth() {
        return $this->tilewidth;
    }
    
    public function setTilewidth($value) {
        $this->tilewidth = $value;
    }
    
    public function getTileheight() {
        return $this->tileheight;
    }
    
    public function setTileheight($value) {
        $this->tileheight = $value;
    }
    
    public function getMatrixwidth() {
        return $this->matrixwidth;
    }
    
    public function setMatrixwidth($value) {
        $this->matrixwidth = $value;
    }
    
    public function getMatrixheight() {
        return $this->matrixheight;
    }
    
    public function setMatrixheight($value) {
        $this->matrixheight = $value;
    }
    
    public function getAsArray() {
        $tilematrix = array();
        $tilematrix["identifier"] = $this->getIdentifier();
        $tilematrix["scaledenominator"] = $this->getScaledenominator();
        $tilematrix["topleftcorner"] = $this->getTopleftcorner();
        $tilematrix["tilewidth"] = $this->getTilewidth();
        $tilematrix["tileheight"] = $this->getTileheight();
        $tilematrix["matrixwidth"] = $this->getMatrixwidth();
        $tilematrix["matrixheight"] = $this->getMatrixheight();
        return $tilematrix;
    }
}
?>
