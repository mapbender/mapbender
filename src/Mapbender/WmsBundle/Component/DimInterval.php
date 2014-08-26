<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimInterval extends DimExtent
{
    public function __toString()
    {
        return implode('/', $this->getValue());
    }
}
