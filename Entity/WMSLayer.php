<?php
namespace MB\WMSBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use MB\WMSBundle\Entity\GroupLayer;

/**
 * @ORM\Entity
*/
class WMSLayer extends GroupLayer {


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
}
