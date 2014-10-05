<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class Identifier
{

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

    /**
     * Get authority
     * 
     * @return string
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /**
     * Set authority
     * @param string $value
     * @return Identifier
     */
    public function setAuthority($value)
    {
        $this->authority = $value;
        return $this;
    }

    /**
     * Get value
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value
     * @param string $value 
     * @return Identifier
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

}
