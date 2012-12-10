<?php

namespace Mapbender\CoreBundle\Component;

/**
 * The class with utility functions.
 *
 * @deprecated Will be replaced by OWSProxy3
 * @author Paul Schmidt
 */
class Utils {
    
    public static function getBool($bool, $nullable = false){
        if($nullable){
            return $bool;
        } else {
            return $bool === null ? false : $bool;
        }
    }
}
