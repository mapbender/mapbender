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
    public $usetunnel = false;

    public function getVstype()
    {
        return $this->vstype;
    }

    public function setVstype($vstype)
    {
        $this->vstype = $vstype;
        return $this;
    }

    public function getUsetunnel()
    {
        return $this->usetunnel;
    }
    
    public function setExtent($extent)
    {
        $this->extent = $this->origextentextent = $extent;
        return $this;
    }
    
    public function getOrigextent()
    {
        if(!$this->origextentextent){
            $this->origextentextent = $this->extent;
        }
        return $this->origextentextent;
    }

    public function setUsetunnel($usetunnel)
    {
        $this->usetunnel = $usetunnel;
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

    public function getConfiguration()
    {
        $array = parent::getConfiguration();
        $array['usetunnel'] = $this->getUsetunnel();
        $array['vstype'] = $this->getVstype();
        return $array;
    }

}
