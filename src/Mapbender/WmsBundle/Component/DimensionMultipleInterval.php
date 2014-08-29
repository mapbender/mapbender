<?php

namespace Mapbender\WmsBundle\Component;

/**
 * DimensionMultipleInterval extends class Dimension. The extent is represented as array of intervals.
 *
 * @author Paul Schmidt
 */
class DimensionMultipleInterval extends Dimension
{
    public function extentStr()
    {
        $array = array_merge($this->getExtent());
        for($i = 0; $i < count($array); $i++){
            $array[$i] = implode('/', $array[$i]);
        }
        return implode(',', $array);
    }
}
