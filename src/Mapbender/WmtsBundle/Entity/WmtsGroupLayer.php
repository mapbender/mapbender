<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="wmtsgrouplayer")
 */
class WmtsGroupLayer extends WmtsLayer {

    /**
     * @ORM\ManyToOne(targetEntity="WMTSGroupLayer",inversedBy="wmtslayer", cascade={"update","delete"})
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=false)
    */
     protected $parent;

    /**
     * @ORM\OneToMany(targetEntity="WMTSGroupLayer",mappedBy="parent", cascade={"persist","remove"})
    */
    protected $layer;
    
    public function __construct(){
        $this->layer = new ArrayCollection();
    }
    
    public function addLayer(WmtsLayerDetail $layer){
        $this->layer->add($layer);
    }
    
    public function getLayer(){
        return $this->layer;
    }

    public function setLayer($layer){

        #WORKAROUND: form some reason $layer is an array of arrays instead of an array of WMTSLayerobjects

        $this->layer = new ArrayCollection();
        $newLayer = null;
        foreach ($layer as $l ){
            $newLayer = new WmtsLayerDetail(); 
            $newLayer->setName($l['name']);
            $newLayer->setTitle($l['title']);
            $newLayer->setAbstract($l['abstract']);
            $this->layer->add($newLayer);
        }

    }

    /**
     * sets the Parent layer
     */
    public function setParent(WmtsLayer $parent){
        $this->parent = $parent;
    }
    
    /**
     * gets the Parent layer
     */
    public function getParent(){
        return $this->parent;
    }

}
