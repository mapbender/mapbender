<?php
namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class Identifier {
    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $authority;
    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $value;
    
//    /**
//     * Creates a Identifier object from parameters
//     * @param array $parameters
//     */
//    public static function create($parameters){
//        $obj = new Identifier();
//        if(isset($parameters["authority"])){
//            $obj->authority = Authority::create($parameters["authority"]);
//        }
//        if(isset($parameters["value"])){
//            $obj->value = $parameters["value"];
//        }
//        return $obj;
//    }
    
    /**
     * Get authority
     * 
     * @return string
     */
    public function getAuthority() {
        return $this->authority;
    }
    
    /**
     * Set authority
     * @param string $value
     * @return Identifier
     */
    public function setAuthority($value) {
        $this->authority = $value;
        return $this;
    }
    
    /**
     * Get value
     * 
     * @return string
     */
    public function getValue() {
        return $this->value;
    }
    
    /**
     * Set value
     * @param string $value 
     * @return Identifier
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }
    
//    /**
//     * Get object as array
//     * 
//     * @return array
//     */
//    public function toArray() {
//        return array (
//            "authority" => $this->authority->toArray(),
//            "value" => $this->getValue()
//        );
//    }
}