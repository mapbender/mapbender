<?php
namespace Mapbender\CoreBundle\Utils;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionProperty;

/**
 * Description of EntityUtils
 *
 * @author Paul Schmidt
 */
class EntityUtil
{
    const GET        = 'get';
    const SET        = 'set';
    const ADD        = 'add';
    const HAS        = 'has';
    const IS         = 'is';
    const GETTER     = 'getter';
    const SETTER     = 'setter';
    const TOSET      = 'toset';
    const HAS_METHOD = 'hasMethod';
    const IS_METHOD  = 'isMethod';

    /**
     * Returns an unique value for an unique field.
     *
     * @param EntityManagerInterface $em
     * @param string $entityName
     * @param string $uniqueField name of the unique field
     * @param string $toUniqueValue value to the unique field
     * @param string $suffix suffix to generate an unique value
     * @return string an unique value
     */
    public static function getUniqueValue(EntityManagerInterface $em, $entityName, $uniqueField, $toUniqueValue, $suffix = "")
    {
        $criteria               = array();
        $criteria[$uniqueField] = $toUniqueValue;
        $obj                    = $em->getRepository($entityName)->findOneBy($criteria);
        if ($obj === null) {
            return $toUniqueValue;
        } else {
            $count = 0;
            do {
                $newUniqueValue         = $toUniqueValue . $suffix . ($count > 0 ? $count : '');
                $count++;
                $criteria[$uniqueField] = $newUniqueValue;
            } while ($em->getRepository($entityName)->findOneBy($criteria));
            return $newUniqueValue;
        }
    }

    /**
     * Gets the real class name of an object that could be an object of proxy class.
     * @param mixed $entity string | entity object
     * @return string full class name
     */
    public static function getRealClass($entity)
    {
        $objClass = "";
        if (is_object($entity)) {
            $objClass = ClassUtils::getClass($entity);
        } elseif (is_string($entity)) {
            $objClass = ClassUtils::getRealClass($entity);
        }
        return $objClass;
    }

    /**
     * Returns a "getter" method name for an property equal to Doctrine conventions.
     * @param mixed $entity object or class
     * @param string $property a property name
     * @return string a "getter" name
     */
    public static function getGetter($entity, $property)
    {
        $temp = 'get' . strtolower(str_replace('_', '', $property));
        foreach (get_class_methods(self::getRealClass($entity)) as $method) {
            if (strtolower($method) === $temp) {
                return $method;
            }
        }
        return null;
    }

    /**
     * Returns a "getter" method name for an property equal to Doctrine conventions.
     * @param mixed $entity object or class
     * @param string $property a property name
     * @return string a "getter" name
     */
    public static function getValueFromGetter($entity, $property)
    {
        $reflMeth = new \ReflectionMethod(self::getRealClass($entity), self::getGetter($entity, $property));
        return $reflMeth->invoke($entity);
    }

    /**
     * Returns a "getter" method name for an property equal to Doctrine conventions.
     * @param mixed $entity object or class
     * @param string $property a property name
     * @return string a "getter" name
     */
    public static function getSetter($entity, $property)
    {
        $temp = 'set' . strtolower(str_replace('_', '', $property));
        foreach (get_class_methods(self::getRealClass($entity)) as $method) {
            if (strtolower($method) === $temp) {
                return $method;
            }
        }
        return null;
    }

    /**
     * @param      $entity
     * @param null $filter
     * @return \ReflectionProperty[]
     */
    public static function getProperties($entity, $filter = null)
    {
        $filter   = $filter === null ? ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED : $filter;
        $refClass = new ReflectionClass(self::getRealClass($entity));
        $props    = $refClass->getProperties($filter);
        return $props;
    }

    /**
     * @param $ormType
     * @return int|string
     * @throws \Exception
     */
    public static function getDataType($ormType)
    {
        switch ($ormType) {
            case 'int':
                return \PDO::PARAM_INT;
            case 'integer':
                return \PDO::PARAM_INT;
            case 'string':
                return \PDO::PARAM_STR;
            case 'text':
                return \PDO::PARAM_STR;
            case 'boolean':
                return \PDO::PARAM_BOOL;
            case 'object':
                return 'object';
            case 'array':
                return 'array';
            case 'decimal':
                return 'decimal';
            case 'date':
                return 'date';
            case 'time':
                return 'time';
            case 'datetime':
                return 'datetime';
            default:
                throw new \Exception('data type is not supported.');
        }
    }

    /**
     * @param                 $fieldName
     * @param ReflectionClass $class
     * @return null|\ReflectionMethod
     */
    public static function getReturnMethod($fieldName, ReflectionClass $class)
    {
        $method = null;
        if ($method = self::getMethodName($fieldName, self::GET, $class)) {
            return $method;
        } elseif ($method = self::getMethodName($fieldName, self::IS, $class)) {
            return $method;
        } elseif ($method = self::getMethodName($fieldName, self::HAS, $class)) {
            return $method;
        }
    }

    /**
     * @param                 $fieldName
     * @param ReflectionClass $class
     * @return null|\ReflectionMethod
     */
    public static function getSetMethod($fieldName, ReflectionClass $class)
    {
        $method = null;
        if ($method = self::getMethodName($fieldName, self::SET, $class)) {
            return $method;
        } elseif ($method = self::getMethodName($fieldName, self::ADD, $class)) {
            return $method;
        }
    }

    /**
     * @param                 $fieldName
     * @param                 $prefix
     * @param ReflectionClass $class
     * @return null|\ReflectionMethod
     */
    public static function getMethodName($fieldName, $prefix, ReflectionClass $class)
    {
        $methodHash = "";
        foreach (preg_split("/_/", $fieldName) as $chunk) {
            $chunk = ucwords($chunk);
            $methodHash .= $chunk;
        }
        if ($class->hasMethod($prefix . $methodHash)) {
            return $class->getMethod($prefix . $methodHash);
        } else {
            return null;
        }
    }
}
