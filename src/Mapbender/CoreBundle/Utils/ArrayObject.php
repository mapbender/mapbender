<?php
namespace Mapbender\CoreBundle\Utils;

use Doctrine\ORM\PersistentCollection;

/**
 * Description of ArrayObject
 *
 * @author paul
 */
class ArrayObject
{
    const GETTER = EntityUtil::GETTER;
    const SETTER = EntityUtil::SETTER;

    /**
     * Transforms an array to an object.
     * 
     * @param string  $classname
     * @param array|object $data
     * @return object|null
     */
    public static function arrayToObject($classname, $data)
    {
        if (is_array($data)) {
            $fields    = EntityAnnotationParser::parseFieldsAnnotations($classname, false);
            $reflClass = new \ReflectionClass($classname);
            $object    = $reflClass->newInstanceArgs(array());
            foreach ($fields as $fieldname => $fieldProps) {
                if (isset($fieldProps[self::SETTER]) && isset($data[$fieldname])) {
                    $reflMethod = new \ReflectionMethod($classname, $fieldProps[self::SETTER]);
                    $reflMethod->invoke($object, $data[$fieldname]);
                }
            }
            return $object;
        } elseif (is_object($data)) {
            return $data;
        } else {
            return null;
        }
    }

    /**
     * Transforms an object to an array.
     *
     * @param array|object|PersistentCollection $data
     * @return array | null
     */
    public static function objectToArray($data)
    {
        if (is_array($data)) {
            return $data;
        } elseif ($data instanceof PersistentCollection) {
            $array = array();
            foreach ($data as $item) {
                $array[] = self::objectToArray($item);
            }
            return $array;
        } elseif (is_object($data)) {
            $fields = EntityAnnotationParser::parseFieldsAnnotations(get_class($data), false);
            $array  = array();
            foreach ($fields as $fname => $fieldProps) {
                if (isset($fieldProps[self::GETTER])) {
                    $reflMethod        = new \ReflectionMethod(get_class($data), $fieldProps[self::GETTER]);
                    $array[$fname] = self::objectToArray($reflMethod->invoke($data));
                }
            }
            return $array;
        } else {
            return $data;
        }
    }
}
