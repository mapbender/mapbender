<?php

namespace Mapbender\CoreBundle\Component;

/**
 * The class with utility functions.
 *
 * @author Paul Schmidt
 */
class Utils {
    
    /**
     * Checks the variable $booleanOrNull and returns the boolean or null.
     * @param type $booleanOrNull
     * @param type $nullable
     * @return boolean if $nullable is false, otherwise boolean or null.
     */
    public static function getBool($booleanOrNull, $nullable = false){
        if($nullable){
            return $booleanOrNull;
        } else {
            return $booleanOrNull === null ? false : $booleanOrNull;
        }
    }
}
