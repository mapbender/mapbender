<?php
namespace MB\WMSBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use MB\CoreBundle\Entity\Keyword;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class WMSService extends GroupLayer {

    /**
    * @ORM\Column(type="string")
    */
    protected $version = "";

    /**
    * @ORM\Column(type="string")
    */
    protected $fees = "";
    
    /**
    * @ORM\Column(type="string")
    */
    protected $accessConstraints = "";
    
    /**
     * @ORM\ManyToMany(targetEntity="MB\CoreBundle\Entity\Keyword")
    */
    protected $keywords;
    

    public function __construct() {
        $this->keywords = new ArrayCollection();
        # calling super  - how to avoid ?
        return parent::__construct();
    }

    public function getKeywords(){
        return $this->keywords;
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
    
}
