<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * TileMatrix class
 *
 * @author Paul Schmidt
 */
class TileMatrix {
    /**  @var string identifier */
    protected $identifier;
    /**  @var string scaledenominator */
    protected $scaledenominator;
    /**  @var string topleftcorner */
    protected $topleftcorner;
    /**  @var string tilewidth */
    protected $tilewidth;
    /**  @var string tileheight */
    protected $tileheight;
    /**  @var string matrixwidth */
    protected $matrixwidth;
    /**  @var string matrixheight */
    protected $matrixheight;
    /**
     * Create an instance of TileMatrix
     * 
     * @param array $tilematrix
     */
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
    /**
     * Get identifier
     * 
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }
    /**
     * Set identifier
     * 
     * @param string $value 
     */
    public function setIdentifier($value) {
        $this->identifier = $value;
    }
    /**
     * Get scaledenominator
     * 
     * @return string 
     */
    public function getScaledenominator() {
        return $this->scaledenominator;
    }
    /**
     * Set scaledenominator
     * @param string $value 
     */
    public function setScaledenominator($value) {
        $this->scaledenominator = $value;
    }
    /**
     * Get topleftcorner
     * 
     * @return string 
     */
    public function getTopleftcorner() {
        return $this->topleftcorner;
    }
    /**
     * Set topleftcorner
     * 
     * @param string $value 
     */
    public function setTopleftcorner($value) {
        $this->topleftcorner = $value;
    }
    /**
     * Get tilewidth
     * 
     * @return string
     */
    public function getTilewidth() {
        return $this->tilewidth;
    }
    /**
     * Set tilewidth
     * 
     * @param string $value 
     */
    public function setTilewidth($value) {
        $this->tilewidth = $value;
    }
    /**
     * Get tileheight
     * 
     * @return string
     */
    public function getTileheight() {
        return $this->tileheight;
    }
    /**
     * Set tileheight
     * 
     * @param string $value 
     */
    public function setTileheight($value) {
        $this->tileheight = $value;
    }
    /**
     * Get matrixwidth
     * 
     * @return string
     */
    public function getMatrixwidth() {
        return $this->matrixwidth;
    }
    /**
     * Set matrixwidth
     * 
     * @param string $value 
     */
    public function setMatrixwidth($value) {
        $this->matrixwidth = $value;
    }
    /**
     * Get matrixheight
     * @return string
     */
    public function getMatrixheight() {
        return $this->matrixheight;
    }
    /**
     * Set matrixheight
     * 
     * @param string $value 
     */
    public function setMatrixheight($value) {
        $this->matrixheight = $value;
    }
    /**
     * Get Tilematrix as array of string
     * 
     * @return array
     */
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
