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
    const TYPE_VS_USER = 'user';
    const TYPE_VS_GROUP = 'groups';

    /**
     * ORM\Column(type="string", nullable=false)
     */
    protected $vstype;

    /**
     * ORM\Column(type="string", nullable=false)
     */
    protected $hidden = false;

    public function getVstype()
    {
        return $this->vstype;
    }

    public function setVstype($vstype)
    {
        $this->vstype = $vstype;
        return $this;
    }

    public function getHidden()
    {
        return $this->hidden;
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

    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
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
        $array['hidden'] = $this->getHidden();
        $array['vstype'] = $this->getVstype();
        return $array;
    }

}
