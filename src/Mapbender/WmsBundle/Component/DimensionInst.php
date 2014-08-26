<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimensionInst extends Dimension
{

    public $use = false;
    
    public function getUse()
    {
        return $this->use;
    }

    public function setUse($use)
    {
        $this->use = $use;
        return $this;
    }


}
