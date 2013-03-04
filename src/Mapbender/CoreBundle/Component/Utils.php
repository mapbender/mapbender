<?php

namespace Mapbender\CoreBundle\Component;

/**
 * The class with utility functions.
 *
 * @author Paul Schmidt
 */
class Utils
{

    /**
     * Checks the variable $booleanOrNull and returns the boolean or null.
     * @param type $booleanOrNull
     * @param type $nullable
     * @return boolean if $nullable is false, otherwise boolean or null.
     */
    public static function getBool($booleanOrNull, $nullable = false)
    {
        if($nullable)
        {
            return $booleanOrNull;
        } else
        {
            return $booleanOrNull === null ? false : $booleanOrNull;
        }
    }

    /**
     * Generats an URL from base url and GET parameters
     * 
     * @param string $baseUrl A base URL
     * @param string $parameters GET Parameters as array or as string
     * @return generated Url
     */
    public static function getHttpUrl($baseUrl, $parameters)
    {
        $url = "";
        $pos = strpos($baseUrl, "?");
        if($pos === false)
        {
            $url = $baseUrl . "?";
        } else if(strlen($baseUrl) - 1 !== $pos)
        {
            $pos = strpos($baseUrl, "&");
            if($pos === false)
            {
                $url = $baseUrl . "&";
            } else if(strlen($baseUrl) - 1 !== $pos)
            {
                $url = $baseUrl . "&";
            } else
            {
                $url = $baseUrl;
            }
        } else
        {
            $url = $baseUrl;
        }
        if(is_string($parameters))
        {
            return $url . $parameters;
        } else if(is_array($parameters))
        {
            $params = array();
            foreach($parameters as $key => $value)
            {
                if(is_string($key))
                {
                    $params[] = $key . "=" . $value;
                } else
                {
                    $params[] = $value;
                }
            }
            return $url . implode("&", $params);
        }
        return null;
    }

}
