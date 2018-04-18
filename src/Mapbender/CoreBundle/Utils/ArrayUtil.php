<?php
namespace Mapbender\CoreBundle\Utils;

/**
 * Description of ArrayUtil
 *
 * @author Paul Schmidt
 */
class ArrayUtil
{
    /**
     * Is array associative
     *
     * @param $array
     * @return bool
     */
    public static function isAssoc($array)
    {
        foreach (array_keys($array) as $key) {
            if (!is_int($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get value from array
     *
     * @param array $list
     * @param null  $value
     * @param int   $default
     * @return mixed|null
     */
    public static function getValueFromArray(array $list, $value = null, $default = 0)
    {
        if (count($list) > 0) {
            $default = is_int($default) && $default < count($list) ? $default : 0;
            if (!self::isAssoc($list)) {
                return $value && in_array($value, $list) ? $value : $list[$default];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Check if array has a key and return the value, other way set new one and return it.
     *
     * @deprecated THIS MODIFIES THE ARRAY BY WRITING THE KEY INTO THE KEY NOT THE VALUE YOU HAVE BEEN WARNED
     * @internal
     *
     * @param array $arr array
     * @param string $key array key to check for existens
     * @param null  $value default value if key doesn't exists
     * @return mixed new value
     */
    public static function hasSet(array &$arr, $key, $value = null){
        if(isset($arr[$key])){
            return $arr[$key];
        }else{
            $arr[$key] = $key;
            return $value;
        }
    }

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
