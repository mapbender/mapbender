<?php
namespace Mapbender\CoreBundle\Utils;


use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Mapbender\Component\StringUtil;
use ReflectionClass;

/**
 * @author Paul Schmidt
 */
class EntityUtil
{
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
     * @param string $propertyName
     * @return string
     */
    public static function getGetter($entity, $propertyName)
    {
        $temp = 'get' . strtolower(str_replace('_', '', $propertyName));
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
     * @param                 $fieldName
     * @param ReflectionClass $class
     * @return null|\ReflectionMethod
     */
    public static function getReturnMethod($fieldName, ReflectionClass $class)
    {
        $prefixes = array(
            'get',
            'is',  // for some boolean entity properties
        );
        $camelCased = StringUtil::snakeToCamelCase($fieldName, true);

        foreach ($prefixes as $prefix) {
            $name = $prefix . $camelCased;
            try {
                return $class->getMethod($name);
            } catch (\ReflectionException $e) {
                // do nothing
            }
        }
        return null;
    }

    /**
     * @param                 $fieldName
     * @param ReflectionClass $class
     * @return null|\ReflectionMethod
     */
    public static function getSetMethod($fieldName, ReflectionClass $class)
    {
        $name = 'set' . StringUtil::snakeToCamelCase($fieldName, true);
        try {
            return $class->getMethod($name);
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    /**
     * @param object $target
     * @param object $source
     * @param ClassMetadata $classMeta
     * @param bool $includeIdent
     */
    public static function copyEntityFields($target, $source, ClassMetadata $classMeta, $includeIdent = false)
    {
        $reflectionClass = $classMeta->getReflectionClass();
        $fieldNames = $classMeta->getFieldNames();
        if (!$includeIdent) {
            $fieldNames = array_diff($fieldNames, $classMeta->getIdentifier());
        }
        foreach ($fieldNames as $fieldName) {
            $getter = static::getReturnMethod($fieldName, $reflectionClass);
            $setter = static::getSetMethod($fieldName, $reflectionClass);
            if ($getter && $setter) {
                $value = $getter->invoke($source);
                if (is_object($value)) {
                    $value = clone $value;
                }
                $setter->invoke($target, $value);
            }
        }
    }
}
