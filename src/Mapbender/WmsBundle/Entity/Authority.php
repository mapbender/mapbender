<?php
namespace Mapbender\WmsBundle\Entity;

/**
 * Authority class.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class Authority {
    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $url;
    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $name;
    
    /**
     * Creates a Authority object from parameters
     * @param array $parameters
     */
    public static function create($parameters){
        $obj = new Authority();
        if(isset($parameters["url"])){
            $obj->url = $parameters["url"];
        }
        if(isset($parameters["name"])){
            $obj->name = $parameters["name"];
        }
        return $obj;
    }
    
    /**
     * Gets url
     * 
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }
    
    /**
     * Sets url
     * @param string $value 
     */
    public function setUrl($value) {
        $this->url = $value;
    }
    
    /**
     * Gets name
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * Sets name
     * @param string $value 
     */
    public function setName($value) {
        $this->name = $value;
    }
    
    /**
     * Gets object as array
     * 
     * @return array
     */
    public function toArray() {
        return array (
            "name" => $this->name,
            "url" => $this->url
        );
    }
}
