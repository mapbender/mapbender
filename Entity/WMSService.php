<?php
namespace MB\WMSBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use MB\CoreBundle\Entity\Keyword;
use MB\WMSBundle\Entity\GroupLayer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
*/
class WMSService extends GroupLayer {

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $version = "";

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $fees = "";
    
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $getMapGet = "";

    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $getMapFormats = "";
    
    /**
    * @ORM\Column(type="string",nullable="true")
    */
    protected $accessConstraints = "";
    

    public function __construct() {
        # calling super  - how to avoid ?
        return parent::__construct();
    }

    public function setVersion($version){
        $this->version = $version;
    }
    
    public function getVersion(){
        return $this->version;
    }
    
    public function setFees($fees){
        $this->fees = $fees;
    }
    
    public function getFees(){
        return $this->fees;
    }
    
    public function setAccessConstraints($accessConstraints){
        $this->accessConstraints = $accessConstraints;
    }
    
    public function getAccessConstraints(){
        return $this->accessConstraints;
    }
    
    public function setGetMapGet($getMapGet){
        $this->getMapGet = $getMapGet;
    }
    
    public function getGetMapGet(){
        return $this->getMapGet;
    }
    
    public function setGetMapFormats($formats){
        $this->getMapFormats = $formats;
    }
    
    public function getGetMapFormats(){
        return $this->getMapFormats;
    }

    /**
     * returns the default (first) format that a wms supports for getMap requests
    */
    public function getDefaultGetMapFormat(){
        $formats = explode(',',$this->getMapFormats);
        return $formats[0];
    }

   /**
    *
    */ 
    public function getRootLayer(){
        return $this->getLayer()->get(0);
    }

    
}
