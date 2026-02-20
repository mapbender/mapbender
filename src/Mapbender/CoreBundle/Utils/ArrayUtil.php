<?php
namespace Mapbender\CoreBundle\Utils;

class ArrayUtil
{

    /**
     * Extract and return the value (or $default if missing) with given $key from given array.
     *
     * @param array $arr
     * @param string|integer $key
     * @param mixed $default
     * @return mixed
     */
    public static function getDefault(array $arr, $key, $default=null)
    {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        } else {
            return $default;
        }
    }

    /**
     * Extract and return the value (or $default if missing) with given $key from given array. Keys are compared
     * in case-insensitive fashion.
     *
     * @param array $arr
     * @param string|integer $key
     * @param mixed $default
     * @return mixed
     */
    public static function getDefaultCaseInsensitive(array $arr, $key, $default=null)
    {
        // make an equivalent array with all keys lower-cased, then look up $key (also lower-cased) inside it
        // NOTE: if multiple keys exist in the input array that differ only in case, they will fold to a single mapped
        //       value post-strtolower. Due to array_combine behaviour, the value mapped to the LAST such input key
        //       will be used.
        // @todo: evaluate if this is a problem / if we require first-key behavior
        //        (solutions: A. replace getDefault delegation with loop
        //                    B. array_reverse both keys and values before array_combine)
        $lcKeys = array_map('strtolower', array_keys($arr));
        $arrWithLcKeys = array_combine($lcKeys, array_values($arr));
        return static::getDefault($arrWithLcKeys, strtolower($key), $default);
    }
}
