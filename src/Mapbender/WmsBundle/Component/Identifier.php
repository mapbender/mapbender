<?php
namespace Mapbender\WmsBundle\Component;

/**
 * @author Paul Schmidt
 */
class Identifier
{

    /** @var string */
    public $authority;
    /** @var string */
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
     * @param string $value
     * @return $this
     */
    public function setAuthority($value)
    {
        $this->authority = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
