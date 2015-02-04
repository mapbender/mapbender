<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Utils;

/**
 * Description of ArrayObject
 *
 * @author paul
 */
class ArrayObject
{
    /**
     * Transforms an array to an object.
     * 
     * @param string  $classname
     * @param array $data 
     * @return object | null
     */
    public static function arrayToObject($classname, $data){
        if (is_array($data)) {
            $fields = EntityAnnotationParser::parseFieldsAnnotations($classname, false);
            $reflClass = new \ReflectionClass($classname);
            $object = $reflClass->newInstanceArgs(array());
            foreach($fields as $fieldname => $fieldProps){
                if (isset($fieldProps[EntityAnnotationParser::SETTER]) && isset($data[$fieldname])) {
                    $reflMethod = new \ReflectionMethod($classname, $fieldProps[EntityAnnotationParser::SETTER]);
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
     * @param type $data
     * @return array | null
     */
    public static  function objectToArray($data){
        if (is_array($data)) {
            return $data;
        } elseif (is_object($data)) {
            $fields = EntityAnnotationParser::parseFieldsAnnotations(get_class($data), false);
            $array = array();
            foreach($fields as $fieldname => $fieldProps){
                if (isset($fieldProps[EntityAnnotationParser::GETTER])) {
                    $reflMethod = new \ReflectionMethod(get_class($data), $fieldProps[EntityAnnotationParser::GETTER]);
                    $array[$fieldname] = self::objectToArray($reflMethod->invoke($data)); // TODO array with objects ??
                }
            }
            return $array;
        } else {
            return $data;
        }
    }
}
