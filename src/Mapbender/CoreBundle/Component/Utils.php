<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Utils\UrlUtil;

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
        } elseif (strlen($baseUrl) - 1 !== $pos) {
            $pos = strpos($baseUrl, "&");
            if ($pos === false) {
                $url = $baseUrl . "&";
            } elseif (strlen($baseUrl) - 1 !== $pos) {
                $url = $baseUrl . "&";
            } else {
                $url = $baseUrl;
            }
        } else {
            $url = $baseUrl;
        }
        if (is_string($parameters)) {
            return $url . $parameters;
        } elseif (is_array($parameters)) {
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
        } elseif (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if ($file !== '.' && $file !== '..' && (is_file($path . "/" . $file) || is_dir($path . "/" . $file))) {
                    Utils::deleteFileAndDir($path . "/" . $file);
                }
            }
            return @rmdir($path);
        }
    }

    /**
     * DEPRECATED, use Mapbender\CoreBundle\Utils\UrlUtil::validateUrl()
     * Validates an URL
     *
     * @param string $url URL
     * @param array $paramsToRemove  array of lower case parameter names to
     * remove from url
     * @return string URL without parameter $paramName
     */
    public static function validateUrl($url, $paramsToRemove)
    {
        return UrlUtil::validateUrl($url, array(), $paramsToRemove);
    }

    /**
     * Copies an order recursively.
     * @param string $sourceOrder path to source order
     * @param string $destinationOrder path to destination order
     */
    public static function copyOrderRecursive($sourceOrder, $destinationOrder)
    {
        $dir  = opendir($sourceOrder);
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
    public static function replaceKey(array &$data, $keyFrom, $keyTo)
    {
        if (isset($data[$keyFrom])) {
            $data[$keyTo] = &$data[$keyFrom];
            unset($data[$keyFrom]);
        }
    }

    /**
     * Generates an UUID.
     * @return string uuid
     */
    public static function guidv4()
    {
        $data = openssl_random_pseudo_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Returns php memory_limit parsed into a megabyte value.
     * Returns null if memory is unlimited.
     * @return float|null
     */
    public static function getMemoryLimitMegs()
    {
        $memoryLimitStr = ini_get('memory_limit');
        if ($memoryLimitStr == '-1' || $memoryLimitStr == '0' || !strlen($memoryLimitStr)) {
            return null;
        } else {
            $suffix = substr($memoryLimitStr, -1);
            if (strlen($memoryLimitStr) == 1) {
                return 0;
            }
            switch ($suffix) {
                case 'G':
                case 'g':
                    return floatval(substr($memoryLimitStr, 0, -1)) * 1024;
                case 'm':
                case 'M':
                    return floatval(substr($memoryLimitStr, 0, -1));
                case 'k':
                    return floatval(substr($memoryLimitStr, 0, -1)) / 1024;
                default:
                    if (is_numeric($suffix)) {
                        return floatval($memoryLimitStr) / 1024 / 1024;
                    } else {
                        throw new \UnexpectedValueException("Unrecognized memory limit suffix " . var_export($suffix, true));
                    }
            }
        }
    }
}
