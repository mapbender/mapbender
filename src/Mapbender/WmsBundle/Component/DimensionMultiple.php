<?php

namespace Mapbender\WmsBundle\Component;

/**
 * DimensionMultiple extends class Dimension. The extent is represented as array of values.
 *
 * @author Paul Schmidt
 */
class DimensionMultiple extends Dimension
{

    public function extentStr()
    {
        return implode(',', $this->getExtent());
    }

}
