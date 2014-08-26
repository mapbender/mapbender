<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimSingle extends DimExtent
{
    public function __toString()
    {
        return $this->getValue();
    }
}
