<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimensionInstIII
{
    
    public $dimension;

    public $origExtent;
    
    public $use = false;
    
    
    public function getDimension()
    {
        return $this->dimension;
    }

    public function setDimension(Dimension $dimension)
    {
        $this->dimension = $dimension;
        return $this;
    }

    public function getOrigExtent()
    {
        return $this->origExtent;
    }

    public function setOrigExtent($origExtent)
    {
        $this->origExtent = $origExtent;
        return $this;
    }

        
    public function getUse()
    {
        return $this->use;
    }

    public function setUse($use)
    {
        $this->use = $use;
        return $this;
    }
    
    public function __toString()
    {
        return $this->getDimension()->getName();
    }


}
