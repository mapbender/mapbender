<?php
namespace Mapbender\WmsBundle\Entity;

/**
 * Identifier class.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class Identifier {
    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $authority;
    /**
     * ORM\Column(type="string", nullable=true)
     */
    protected $value;
    
    /**
     * Creates a Identifier object from parameters
     * @param array $parameters
     */
    public static function create($parameters){
        $obj = new Identifier();
        if(isset($parameters["authority"])){
            $obj->authority = Authority::create($parameters["authority"]);
        }
        if(isset($parameters["value"])){
            $obj->value = $parameters["value"];
        }
        return $obj;
    }
    
    /**
     * Gets authority
     * 
     * @return Authority
     */
    public function getAuthority() {
        return $this->authority;
    }
    
    /**
     * Sets authority
     * @param Authority $value 
     */
    public function setAuthority($value) {
        $this->authority = $value;
    }
    
    /**
     * Gets value
     * 
     * @return string
     */
    public function getValue() {
        return $this->value;
    }
    
    /**
     * Sets value
     * @param string $value 
     */
    public function setValue($value) {
        $this->value = $value;
    }
    
    /**
     * Gets object as array
     * 
     * @return array
     */
    public function toArray() {
        return array (
            "authority" => $this->authority->toArray(),
            "value" => $this->getValue()
        );
    }
}