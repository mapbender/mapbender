<?php

namespace Mapbender\WmsBundle\Component;

/**
 * DimensionInterval extends class Dimension. The extent is represented as array with fields: start, end, interval.
 *
 * @author Paul Schmidt
 */
class DimensionInterval extends Dimension
{
    public function extentStr()
    {
        return implode('/', $this->getExtent());
    }
}
