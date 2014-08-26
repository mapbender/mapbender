<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimMultiple extends DimExtent
{

    public function __toString()
    {
        return implode(',', $this->getValue());
    }

}
