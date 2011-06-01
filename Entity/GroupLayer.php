<?php
namespace MB\WMSBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class GroupLayer extends Layer {

    /**
     * @ORM\ManyToMany(targetEntity="Layer")
    */
    protected $layer;
    
    public function __construct(){
        $this->layer = new ArrayCollection();
    }
    
    public function getLayer(){
        return $this->layer;
    }

}
