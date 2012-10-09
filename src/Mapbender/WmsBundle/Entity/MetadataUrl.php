<?php
namespace Mapbender\WmsBundle\Entity;

/**
 * MetadataUrl class.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class MetadataUrl {
    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $url;
    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $name;
    
    /**
     * Creates a MetadataUrl object from parameters
     * @param array $parameters
     */
    public static function create($parameters){
        $obj = new MetadataUrl();
        if(isset($parameters["name"])){
            $this->name = $parameters["name"];
        }
        if(isset($parameters["url"])){
            $this->url = $parameters["url"];
        }
        return $obj;
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
     * Gets object as array
     * 
     * @return array
     */
    public function toArray() {
        return array (
            "url" => $this->url,
            "name" => $this->name
        );
    }
}