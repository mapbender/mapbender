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
        if ($nullable) {
            return $booleanOrNull;
        } else {
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
        if ($pos === false) {
            $url = $baseUrl . "?";
        } else if (strlen($baseUrl) - 1 !== $pos) {
            $pos = strpos($baseUrl, "&");
            if ($pos === false) {
                $url = $baseUrl . "&";
            } else if (strlen($baseUrl) - 1 !== $pos) {
                $url = $baseUrl . "&";
            } else {
                $url = $baseUrl;
            }
        } else {
            $url = $baseUrl;
        }
        if (is_string($parameters)) {
            return $url . $parameters;
        } else if (is_array($parameters)) {
            $params = array();
            foreach ($parameters as $key => $value) {
                if (is_string($key)) {
                    $params[] = $key . "=" . $value;
                } else {
                    $params[] = $value;
                }
            }
            return $url . implode("&", $params);
        }
        return null;
    }

    /**
     * Removes a file or directory (recursive)
     *
     * @param string $path tha path of file/directory
     * @return boolean true if the file/directory is removed.
     */
    public static function deleteFileAndDir($path)
    {
        if (is_file($path)) {
            return @unlink($path);
        } else if (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if ($file !== '.' && $file !== '..' && (is_file($path . "/" . $file) || is_dir($path . "/" . $file))) {
                    Utils::deleteFileAndDir($path . "/" . $file);
                }
            }
            return @rmdir($path);
        }
    }

    /**
     * Validates an URL
     *
     * @param string $url URL
     * @param array $paramsToRemove  array of lower case parameter names to
     * remove from url
     * @return string URL without parameter $paramName
     */
    public static function validateUrl($url, $paramsToRemove)
    {
        $rowUrl = parse_url($url);
        $newurl = $rowUrl["scheme"] . "://" . $rowUrl['host'];
        if (isset($rowUrl['port']) && intval($rowUrl['port']) !== 80) {
            $newurl .= ':' . $rowUrl['port'];
        }
        if (isset($rowUrl['path']) && strlen($rowUrl['path']) > 0) {
            $newurl .= $rowUrl['path'];
        }
        $queries = array();
        $getParams = array();
        if (isset($rowUrl["query"])) {
            parse_str($rowUrl["query"], $getParams);
        }
        foreach ($getParams as $key => $value) {
            if (!in_array(strtolower($key), $paramsToRemove)) {
                $queries[] = $key . "=" . $value;
            }
        }
        if (count($queries) > 0) {
            $newurl .= '?' . implode("&", $queries);
        }
        return $newurl;
    }

    public static function copyOrderRecursive($sourceOrder, $destinationOrder)
    {
        $dir = opendir($sourceOrder);
        @mkdir($destinationOrder);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($sourceOrder . '/' . $file)) {
                    Utils::copyOrderRecursive($sourceOrder . '/' . $file, $destinationOrder . '/' . $file);
                } else {
                    copy($sourceOrder . '/' . $file, $destinationOrder . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * If value is null, return first value, else return second value
     *
     * @param      $scaleRecursive
     * @param      $trueValue
     * @param null $nullValue
     * @return null
     */
    public static function valueOrNull($scaleRecursive, $trueValue, $nullValue = null)
    {
        return $scaleRecursive !== null ? $trueValue : $nullValue;
    }


    /**
     * Has a value?
     *
     * @param $data
     * @param $key
     * @param $value
     * @return bool
     */
    public static function hasValue(&$data, $key, $value)
    {
        return isset($data[$key]) && strtolower($data[$key]) == $value;
    }

    /**
     * Replace array key
     *
     * @param array $data
     * @param       $keyFrom
     * @param       $keyTo
     */
    public static function replaceKey(array &$data, $keyFrom, $keyTo )
    {
        if (isset($data[$keyFrom])) {
            $data[$keyTo] = &$data[$keyFrom];
            unset($data[$keyFrom]);
        }
    }
}
