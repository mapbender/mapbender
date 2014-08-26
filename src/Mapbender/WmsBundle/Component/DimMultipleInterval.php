<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimMultipleInterval extends DimExtent
{
    public function __toString()
    {
        $array = array_merge($this->getValue());
        for($i = 0; $i < count($array); $i++){
            $array[$i] = implode('/', $array[$i]);
        }
        return implode(',', $array);
    }
}
