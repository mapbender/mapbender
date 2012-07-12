<?php
namespace Mapbender\WmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * WmsInstance class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 *
 * 
 * @ORM\Entity
*/
class WmsInstance {
    /**
    *  @ORM\Id
    *  @ORM\Column(type="integer")
    *  @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;
    /**
     * @ORM\ManyToOne(targetEntity="WMSService",inversedBy="layer", cascade={"update"})
     * @ORM\JoinColumn(name="service", referencedColumnName="id")
     */
    protected $service;
    /**
     * Layersetid from .yml
     * @ORM\Column(type="string", nullable=true)
     */
    protected $layersetid = true;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $published = false;
    /**
     * Layerid form .yml
     * @ORM\Column(type="string", nullable=true)
     */
    protected $layerid = null;
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $url = null;
    /**
    * @ORM\Column(type="string", nullable=true)
    */
    protected $format = null;
    /**
    * @ORM\Column(type="array", nullable=true)
    */
    protected $layers = array(); //{ name: 1,   title: Webatlas,   visible: true }
    /**
    * @ORM\Column(type="array", nullable=true)
    */
    protected $fulllayers = array(); //{ name: 1,   title: Webatlas,   visible: true }
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $visible = true;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $proxy = false;
//    /**
//    * @ORM\Column(type="integer", nullable=true)
//    */
//    protected $layeridentifier = null;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $baselayer = true;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $transparent = true;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $tiled = false;
    /**
    * @ORM\Column(type="array", nullable=true)
    */
    protected $srs = array();
    /**
     * Gets id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }
    
     /**
     * Sets a id
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getService(){
        return $this->service;
    }
    
    public function setService($service){
        $this->service = $service;
    }
    
    /**
     * Get the layerid
     *
     * @return string
     */
    public function getLayerid() {
        return $this->layerid;
    }
    /**
     * Set a layerid
     *
     * @param string $layerid
     */
    public function setLayerid($layerid) {
        $this->layerid = $layerid;
    }
    /**
     * Get the layersetid
     *
     * @return string
     */
    public function getLayersetid() {
        return $this->layersetid;
    }
    /**
     * Set a layersetid
     *
     * @param string $layersetid
     */
    public function setLayersetid($layersetid) {
        $this->layersetid = $layersetid;
    }
    
    
    /**
     * Gets visible
     *
     * @return boolean
     */
    public function getVisible() {
        return $this->visible;
    }
    
     /**
     * Sets an visible
     *
     * @param boolean $visible
     */
    public function setVisible($visible) {
        $this->visible = $visible;
    }
    
    /**
     * Gets proxy
     *Layerid
     * @return boolean
     */
    public function getProxy() {
        return $this->proxy;
    }
    /**
     * Sets an proxy
     *
     * @param boolean $proxy
     */
    public function setProxy($proxy) {
        $this->proxy = $proxy;
    }

    /**
     * Get the format
     *
     * @return string
     */
    public function getFormat() {
        return $this->format;
    }
    /**
     * Set a format
     *
     * @param string $format
     */
    public function setFormat($format) {
        $this->format = $format;
    }
  
    /**
     * Get an url
     *
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }
    /**
     * Set an url
     *
     * @param string $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }
    /**
     * Get the layers
     *
     * @return array
     */
    public function getLayers() {
        return $this->layers;
    }
    /**
     * Set the layers
     *
     * @param array $layers
     */
    public function setLayers($layers) {
        if($layers === null) {
            $this->layers = array();
        } else if(is_string($layers)) {
            $this->layers = explode(",", $layers);
        } else if(is_array($layers)) {
            $this->layers = $layers;
        } else {
            $this->layers = array();
        }
        
    }
    
    /**
     * Get the layers
     *
     * @return array
     */
    public function getFulllayers() {
        return $this->fulllayers;
    }
    /**
     * Set the layers
     *
     * @param array $layers
     */
    public function setFulllayers($layers) {
        if($layers === null) {
            $this->fulllayers = array();
        } else if(is_string($layers)) {
            $this->fulllayers = explode(",", $layers);
        } else if(is_array($layers)) {
            $this->fulllayers = $layers;
        } else {
            $this->fulllayers = array();
        }
        
    }
    /**
     * Set the layers
     *
     * @param array $layers
     */
    public function addLayer($layer) {
        if(is_array($layer)) {
            $this->layers[] = $layer;
        }
    }

    /**
     * Get a baselayer
     * 
     * @return boolean
     */
    public function getBaselayer() {
        return $this->baselayer;
    }
    /**
     * Set a baselayer
     *
     * @param boolean $baselayer
     */
    public function setBaselayer($baselayer) {
        $this->baselayer = $baselayer;
    }

    /**
     * Get a transparent
     * 
     * @return boolean
     */
    public function getTransparent() {
        return $this->transparent;
    }
    /**
     * Sets a transparent
     *
     * @param boolean $transparent
     */
    public function setTransparent($transparent) {
        $this->transparent = $transparent;
    }

    /**
     * Get a tiled
     * 
     * @return boolean
     */
    public function getTiled() {
        return $this->tiled;
    }
    /**
     * Sets a tiled
     *
     * @param boolean $tiled
     */
    public function setTiled($tiled) {
        $this->tiled = $tiled;
    }
    
    /**
     * Get a published
     * 
     * @return boolean
     */
    public function getPublished() {
        return $this->published;
    }
    /**
     * Sets a published
     *
     * @param boolean $published
     */
    public function setPublished($published) {
        $this->published = $published;
    }
    /**
     * Get the srs
     *
     * @return array
     */
    public function getSrs() {
        return $this->srs;
    }
    /**
     * Set the srs
     *
     * @param array $srs
     */
    public function setSrs($srs) {
        $this->srs = $srs;
    }
    /**
     * Set the srs
     *
     * @param string $srs
     */
    public function addSrs($srs){
        if($this->srs !== null){
            if(srs !== null && !in_array($srs, $this->srs)){
                $this->srs[] = $srs;
            }
        } else {
            if(srs !== null){
                $this->srs = array();
                $this->srs[] = $srs;
            }
        }
    }
    /**
     * Has the srs
     *
     * @param string $srs
     */
    public function hasSrs($srs){
        if($this->srs === null){
            return false;
        } else {
            return in_array($srs, $this->srs);
        }
    }
  
    public function getLayerArray($name) {
        foreach ($this->layers as $layer) {
            if($layer["name"] == $name) {
                return $layer;
            }
        }
        return null;
    }
    
    public function getFullLayerArray($name) {
        foreach ($this->fulllayers as $layer) {
            if($layer["name"] == $name) {
                return $layer;
            }
        }
        return null;
    }
    
    public function getCompletedFullLayerArray($name){
        foreach ($this->fulllayers as $fulllayer) {
            if($fulllayer["name"] == $name) {
                $layer = $this->getLayerArray($name);
                if($layer !== null){
//                    $fulllayer["published"] = $layer["published"];
                    $fulllayer["visible"] = $layer["visible"];
                    $fulllayer["queryable"] = isset($layer["queryable"]) ? $layer["queryable"] : null;
                }
                return $fulllayer;
            }
        }
        return null;
    }
    
    public static function removeFromLayerArray($array, $name){
        $newarray = array();
        foreach ($array as $field) {
            if($field["name"] != $name){
                $newarray[] = $field;
            }
        }
        return $newarray;
    }
    
    public function save($em){
        if($this->service !== null && count($this->getFulllayers()) == 0){
            $fulllayers = array();
            foreach ($this->service->getAllLayer() as $layer) {
                if($layer->getName() !== null && $layer->getName() != ""){
                    $instanceLayer = WmsInstanceLayer::create(
                            $this->getId(),
                            $layer->getId(),
                            $layer->getName(),
                            $layer->getTitle(),
                            false, 
                            false, 
                            $layer->getQueryable()? false : null);
                    $fulllayers[] = $instanceLayer->getAsFullArray();
                }
            }
            $this->setFulllayers($fulllayers);
        }
        $em->persist($this);
        $em->flush();
    }
    
    public function completeForm($translator, $form) {
        if( $this->service !== null){
            $form->add('published', 'checkbox', array(
                'label' => $translator->trans('published').":",
                'required'  => false));
            $form->add('layersetid', 'text', array(
                'label' => $translator->trans('layersetid').":",
                'required'  => false,
                'read_only' => true));
            $form->add('layerid', 'text', array(
                'label' => $translator->trans('layer_id').":",
                'required'  => false));
            $form->add('url', 'text', array(
                'label' => $translator->trans('url').":",
                'required'  => false));
            $arr = $this->service->getRequestGetMapFormats()!== null?
                    $this->service->getRequestGetMapFormats(): array();
            $formats = array();
            foreach ($arr as $value) {
                $formats[$value] = $value;
            }
            $form->add('format', 'choice', array(
                'label' => $translator->trans('format').":",
                'choices' => $formats,
                'required'  => true));
            $form->add('visible', 'checkbox', array(
                'label' => $translator->trans('visible').":",
                'required'  => false));

            $form->add('proxy', 'checkbox', array(
                'label' => $translator->trans('proxy').":",
                'required'  => false));

            $form->add('baselayer', 'checkbox', array(
                'label' => $translator->trans('baselayer').":",
                'required'  => false));
            $form->add('transparent', 'checkbox', array(
                'label' => $translator->trans('transparent').":",
                'required'  => false));
            $form->add('tiled', 'checkbox', array(
                'label' => $translator->trans('tiled').":",
                'required'  => false));
        }
        return $form;
    }
}