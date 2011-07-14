<?php
namespace MB\WMSBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class GroupLayer extends Layer {

    /**
     * @ORM\ManyToOne(targetEntity="GroupLayer",inversedBy="layer")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
    */
     protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="GroupLayer",mappedBy="parent", cascade={"all"})
    */
    protected $layer;
    
    public function __construct(){
        $this->layer = new ArrayCollection();
    }
    
    public function addLayer(WMSLayer $layer){
        $this->layer->add($layer);
    }
    
    public function getLayer(){
        return $this->layer;
    }

    public function setLayer($layer){

        #WORKAROUND: form some reason $layer is an array of arrays instead of an array of WMSLayerobjects

        $this->layer = new ArrayCollection();
        $newLayer = null;
        foreach ($layer as $l ){
            $newLayer = new WMSLayer(); 
            $newLayer->setName($l['name']);
            $newLayer->setTitle($l['title']);
            $newLayer->setAbstract($l['abstract']);
            $this->layer->add($newLayer);
        }

    }

}
