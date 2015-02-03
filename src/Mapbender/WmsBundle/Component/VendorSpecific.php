<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class VendorSpecific extends DimensionInst
{

    const TYPE_VS_SIMPLE = 'simple';
    const TYPE_VS_USERNAME = 'username';
    const TYPE_VS_GROUPNAME = 'groupname';

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $vstype;

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $useTunnel = false;

    public function getVstype()
    {
        return $this->vstype;
    }

    public function setVstype($vstype)
    {
        $this->vstype = $vstype;
        return $this;
    }

    public function getUseTunnel()
    {
        return $this->useTunnel;
    }

    public function setUseTunnel($useTunnel)
    {
        $this->useTunnel = $useTunnel;
        return $this;
    }

    /**
     * Generates a GET parameter name for this dimension.
     * @return string parameter name
     */
    public function getParameterName()
    {
        return $this->name;
    }

}
