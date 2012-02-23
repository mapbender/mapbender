<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * TileMatrixSet class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class TileMatrixSet {
    
    protected $title;
    
    protected $abstract;
    
    protected $identifier;
    
    protected $keyword;
    
    protected $supportedsrs;
    
    protected $wellknowscaleset;
    
    protected $tilematrixes;
    
    public function __construct($tilematrixset = null){
        $this->tilematrixes = new ArrayCollection();
        if($tilematrixset!=null & is_array($tilematrixset)){
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


    public function getTitle() {
        return $this->title;
    }
    
    public function setTitle($value) {
        $this->title = $value;
    }
    
    public function getAbstract() {
        return $this->abstract;
    }
    
    public function setAbstract($value) {
        $this->abstract = $value;
    }
    
    public function getIdentifier() {
        return $this->identifier;
    }
    
    public function setIdentifier($value) {
        $this->identifier = $value;
    }
    
    public function getKeyword() {
        return $this->keyword;
    }
    
    public function setKeyword($value) {
        $this->keyword = $value;
    }
    
    public function getSupportedSRS() {
        return $this->supportedsrs;
    }
    
    public function setSupportedSRS($value) {
        $this->supportedsrs = $value;
    }
    
    public function getWellknowscaleset() {
        return $this->wellknowscaleset;
    }
    
    public function setWellknowscaleset($value) {
        $this->wellknowscaleset = $value;
    }
    
    public function getTilematrix(){
        return $this->tilematrixes;
    }
    
    public function setTilematrix($tilematrixes){
        $this->tilematrixes = $tilematrixes;
    }
    
    public function addTilematrix($tilematrix){
        if($tilematrix instanceof TileMatrix) {
            $this->tilematrixes->add($tilematrix);
        } else if(is_array($tilematrix)) {
            $this->tilematrixes->add(new TileMatrix($tilematrix));
        }
    }
    
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
?>
