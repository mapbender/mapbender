<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * TileMatrixSet class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class TileMatrixSet {
    /**  @var string title */
    protected $title;
    /**  @var string abstract */
    protected $abstract;
    /**  @var string identifier */
    protected $identifier;
    /**  @var string keyword ??? */
    protected $keyword;
    /**  @var array supportedsrs */
    protected $supportedsrs = array();
    /**  @var string wellknowscaleset */
    protected $wellknowscaleset;
    /**  @var array $tilematrixes */
    protected $tilematrixes;
    /**
     * Create an instance of TileMatrixSet
     * 
     * @param type $tilematrixset 
     */
    public function __construct($tilematrixset = null){
        $this->tilematrixes = new ArrayCollection();
        if($tilematrixset!=null && is_array($tilematrixset)){
            $this->setTitle($tilematrixset["title"]);
            $this->setAbstract($tilematrixset["abstract"]);
            $this->setIdentifier($tilematrixset["identifier"]);
            $this->setKeyword($tilematrixset["keyword"]);
            $this->setSupportedSRS($tilematrixset["supportedsrs"]);
            $this->setWellknowscaleset($tilematrixset["wellknowscaleset"]);
            foreach($tilematrixset["tilematrixes"] as $tilematrix){
                $this->tilematrixes->add(new TileMatrix($tilematrix));
            }
        }
    }

    /**
     * Get title
     * 
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
    /**
     * Set title
     * @param string $value 
     */
    public function setTitle($value) {
        $this->title = $value;
    }
    /**
     * Get abstract
     * @return string
     */
    public function getAbstract() {
        return $this->abstract;
    }
    /**
     * Set abstract
     * @param string $value 
     */
    public function setAbstract($value) {
        $this->abstract = $value;
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
     * Get keyword
     * 
     * @return string
     */
    public function getKeyword() {
        return $this->keyword;
    }
    /**
     * Set keyword
     * 
     * @param string $value 
     */
    public function setKeyword($value) {
        $this->keyword = $value;
    }
    /**
     * Get suppertedsrs
     * 
     * @return string
     */
    public function getSupportedSRS() {
        return $this->supportedsrs;
    }
    /**
     * Set supportedsrs
     * 
     * @param string $value 
     */
    public function setSupportedSRS($value) {
        $this->supportedsrs = $value;
    }
    /**
     * Add supportedsrs
     * 
     * @param string $value 
     */
    public function addSupportedSRS($value) {
        if($this->supportedsrs === null) {
            $this->supportedsrs = array();
        }
        if(!in_array($value, $this->supportedsrs)){
            $this->supportedsrs[] = $value;
        }
    }
    /**
     * Get simple supportedsrs
     * 
     * return array 
     */
    public function getSRS() {
        if($this->supportedsrs === null) {
            return array();
        } else {
            $array = array();
            foreach($this->supportedsrs as $srs) {
                $newsrs = strripos($srs, "EPSG") !== FALSE ? substr($srs, strripos($srs, "EPSG")) : $srs;
                $array[] = str_replace("::" , ":" , $newsrs);
            }
            return $array;
        }
    }
    /**
     * Get wellknowscaleset
     * 
     * @return string
     */
    public function getWellknowscaleset() {
        return $this->wellknowscaleset;
    }
    /**
     * Set wellknowscaleset
     * 
     * @param string $value 
     */
    public function setWellknowscaleset($value) {
        $this->wellknowscaleset = $value;
    }
    /**
     * Get Tilematrix as ArrayCollection of Tilematrix
     * 
     * @return array
     */
    public function getTilematrix(){
        return $this->tilematrixes;
    }
    /**
     * Set tilematrix: ArrayCollection of Tilematrix
     * 
     * @param ArrayCollection $tilematrixes 
     */
    public function setTilematrix($tilematrixes){
        $this->tilematrixes = $tilematrixes;
    }
    /**
     * Add to tilematrix TileMatrix or Tilematrix as array
     * 
     * @param $tilematrix 
     */
    public function addTilematrix($tilematrix){
        if($tilematrix instanceof TileMatrix) {
            $this->tilematrixes->add($tilematrix);
        } else if(is_array($tilematrix)) {
            $this->tilematrixes->add(new TileMatrix($tilematrix));
        }
    }
    /**
     * Get TilematrixSet as array of string inc. TileMatrixes
     * 
     * @return array
     */
    public function getAsArray() {
        $tilematrixset = array();
        $tilematrixset["title"] = $this->getTitle();
        $tilematrixset["abstract"] = $this->getAbstract();
        $tilematrixset["identifier"] = $this->getIdentifier();
        $tilematrixset["keyword"] = $this->getKeyword();
        $tilematrixset["supportedsrs"] = $this->getSupportedSRS();
        $tilematrixset["wellknowscaleset"] = $this->getWellknowscaleset();
        $tilematrix = array();
        foreach($this->getTilematrix() as $tilematrixObj){
            $tilematrix[] = $tilematrixObj->getAsArray();
        }
        $tilematrixset["tilematrixes"] = $tilematrix;
        return $tilematrixset;
    }
}
