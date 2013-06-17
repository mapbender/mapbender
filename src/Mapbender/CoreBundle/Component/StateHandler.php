<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\State;

/**
 * Description of State
 *
 * @author Paul Schmidt
 */
class StateHandler
{
    
    private $id;
    
    private $name;
    
    private $serverurl;
    
    private $slug;
    
    private $window;
    
    private $extent;
    
    private $maxextent;
    
    private $sources = array();
    
    /**
     * Sets id
     * 
     * @param type $value
     * @return StateHandler
     */
    public function setId($value){
        $this->id = $value;
        return $this;
    }
    
    /**
     * Returns id
     * 
     * @return integer
     */
    public function getId(){
        return $this->id;
    }
    
    /**
     * Sets name
     * 
     * @param string $value
     * @return StateHandler
     */
    public function setName($value){
        $this->name = $value;
        return $this;
    }
    
    /**
     * Returns name
     * 
     * @return string
     */
    public function getName(){
        return $this->name;
    }
    
    
    
    /**
     * Sets serverurl
     * 
     * @param string $value
     * @return StateHandler
     */
    public function setServerurl($value){
        $this->serverurl = $value;
        return $this;
    }
    
    /**
     * Returns serverurl
     * 
     * @return string
     */
    public function getServerurl(){
        return $this->serverurl;
    }
    
    
    
    /**
     * Sets slug
     * 
     * @param string $value
     * @return StateHandler
     */
    public function setSlug($value){
        $this->slug = $value;
        return $this;
    }
    
    /**
     * Returns slug
     * 
     * @return string
     */
    public function getSlug(){
        return $this->slug;
    }
    
    
    
    /**
     * Sets window
     * 
     * @param Size $value
     * @return StateHandler
     */
    public function setWindow(Size $value){
        $this->window = $value;
        return $this;
    }
    
    /**
     * Returns window
     * 
     * @return Size
     */
    public function getWindow(){
        return $this->window;
    }
    
    
    
    /**
     * Sets extent
     * 
     * @param BoundingBox $value
     * @return StateHandler
     */
    public function setExtent(BoundingBox $value){
        $this->extent = $value;
        return $this;
    }
    
    /**
     * Returns extent
     * 
     * @return BoundingBox
     */
    public function getExtent(){
        return $this->extent;
    }
    
    
    
    /**
     * Sets maxextent
     * 
     * @param BoundingBox $value
     * @return StateHandler
     */
    public function setMaxextent(BoundingBox $value){
        $this->maxextent = $value;
        return $this;
    }
    
    /**
     * Returns maxextent
     * 
     * @return BoundingBox
     */
    public function getMaxextent(){
        return $this->maxextent;
    }
    
    /**
     * Sets sources
     * 
     * @param array $value
     * @return StateHandler
     */
    public function setSources($value){
        $this->sources = $value;
        return $this;
    }
    
    /**
     * Returns sources
     * 
     * @return array
     */
    public function getSources(){
        return $this->sources;
    }
    
    /**
     * Adds source
     * 
     * @return StateHandler
     */
    public function addSource($value){
        $this->sources[] = $value;
        return $this;
    }
    
    /**
     * Creates a StateHandler from parameters
     * 
     * @param array $json
     * @return StateHandler 
     */
    public static function create($json, $id = null, $name = null, $serverurl = null, $slug = null){
        $sh = new StateHandler();
        $sh->setId($id);
        $sh->setName($name);
        $sh->setServerurl($serverurl);
        $sh->setSlug($slug);
        $sh->setWindow(Size::create($json["window"]));
        $sh->setExtent(BoundingBox::create($json["extent"]));
        $sh->setMaxextent(BoundingBox::create($json["maxextent"]));
        $sh->setSources($json["sources"]);
        return $sh;
    }
    
    public function generateState(){
        $state = new State();
        $state->setTitle($this->name)
                ->setServerurl($this->serverurl)
                ->setSlug($this->slug)
                ->setJson($this->toArray());
        return $state;
    }
    
    public function toArray()
    {
        return array(
            "window" => $this->window->toArray(),
            "extent" => $this->extent->ToArray(),
            "maxextent" => $this->maxextent->ToArray(),
            "sources" => $this->sources);
    }
    
//    
//    /**
//     * Sets
//     * 
//     * @param type $value
//     * @return StateHandler
//     */
//    public function set($value){
//        $this-> = $value;
//        return $this;
//    }
//    
//    /**
//     * Returns 
//     * 
//     * @return integer
//     */
//    public function get(){
//        return $this->;
//    }
    
}

?>
