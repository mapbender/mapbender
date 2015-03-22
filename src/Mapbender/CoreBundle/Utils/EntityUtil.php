<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Utils;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

/**
 * Description of EntityUtils
 *
 * @author Paul Schmidt
 */
class EntityUtil
{

    /**
     * Returns an unique value for an unique field.
     *
     * @param \Doctrine\ORM\EntityManager $em an entity manager
     * @param string $entityName entity name
     * @param string $uniqueField name of the unique field
     * @param string $toUniqueValue value to the unique field
     * @param string $suffix suffix to generate an unique value
     * @return string an unique value
     */
    public static function getUniqueValue(EntityManager $em, $entityName, $uniqueField, $toUniqueValue, $suffix = "")
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
        return '';
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
        return '';
    }
}
