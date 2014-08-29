<?php

namespace Mapbender\WmsBundle\Component;

/**
 * DimensionSingle extends class Dimension. The extent is represented as string value.
 *
 * @author Paul Schmidt
 */
class DimensionSingle extends Dimension
{
    public function extentStr()
    {
        return $this->getExtent();
    }
}
