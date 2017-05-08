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
}