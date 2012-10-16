<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Authority class.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class Authority {

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $url;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $name;

    /**
     * Creates a Authority object from parameters
     * @param array $parameters
     */
    public static function create($parameters) {
        $obj = new Authority();
        if (isset($parameters["url"])) {
            $obj->url = $parameters["url"];
        }
        if (isset($parameters["name"])) {
            $obj->name = $parameters["name"];
        }
        return $obj;
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
     * @return Authority
     */
    public function setUrl($value) {
        $this->url = $value;
        return $this;
    }

    /**
     * Get name
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set name
     * @param string $value 
     * @return Authority
     */
    public function setName($value) {
        $this->name = $value;
        return $this;
    }

    /**
     * Get object as array
     * 
     * @return array
     */
    public function toArray() {
        return array(
            "name" => $this->name,
            "url" => $this->url
        );
    }

}
