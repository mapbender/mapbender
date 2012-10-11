<?php
namespace Mapbender\WmsBundle\Component;

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
    protected $type;
    
    /**
     * Creates a MetadataUrl object from parameters
     * @param array $parameters
     */
    public static function create($parameters){
        $obj = new MetadataUrl();
        if(isset($parameters["type"])){
            $this->type = $parameters["type"];
        }
        if(isset($parameters["url"])){
            $this->url = $parameters["url"];
        }
        return $obj;
    }
    
    /**
     * Get type
     * 
     * @return string
     */
    public function getType() {
        return $this->type;
    }
    
    /**
     * Set type
     * @param string $value 
     * @return MetadataUrl
     */
    public function setType($value) {
        $this->type = $value;
        return $this;
    }
    
    /**
     * Get url
     * 
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }
    
    /**
     * Set url
     * @param string $value 
     * @return MetadataUrl
     */
    public function setUrl($value) {
        $this->url = $value;
        return $this;
    }
    
    /**
     * Get object as array
     * 
     * @return array
     */
    public function toArray() {
        return array (
            "url" => $this->url,
            "type" => $this->type
        );
    }
}