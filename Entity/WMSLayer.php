<?php
namespace MB\WMSBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use MB\WMSBundle\Entity\GroupLayer;

/**
 * @ORM\Entity
*/
class WMSLayer extends GroupLayer {


    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $srs = "";
    
    /**
     * @ORM\Column(type="string",nullable="true")
     */
    protected $latLonBounds = "";

    /**
     * returns the WMSService a WMSLayer belongs to. This is neccessary because WMSLayer::getParent() might return a GroupLayer only
     */
    public function getWMS(){
        $layer = $this;
        // go up until layer becomes falsy
        $parent = $layer->getParent();
        while($parent != null){
            $layer = $parent;
            $parent = $layer->getParent();
        }
        return $layer;
    }

    public function setSrs($srs){
        $this->srs = $srs;
    }
    public function getSrs(){
        return $this->srs;
    }
    public function getDefaultSrs(){
        $srs = explode(',',$this->srs);
        return $srs[0] ?:"";
    }

    public function setLatLonBounds($bounds){
        $this->latLonBounds = $bounds; 
    }

    public function getLatLonBounds(){
        return $this->latLonBounds;
    }
}
