<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


use Mapbender\Component\StringUtil;

abstract class AbstractObjectHelper
{
    /** @var string */
    protected $className;
    /** @var \ReflectionMethod[] */
    protected $setters = array();
    /** @var \ReflectionMethod[] */
    protected $getters = array();
    /** @var string[] */
    protected $propertyNames = array();

    /**
      * @param string $className
      * @throws \ReflectionException
      */
     public function __construct($className)
     {
         $rfl = new \ReflectionClass($className);
         foreach ($rfl->getProperties() as $prop) {
             $propertyName = $prop->getName();
             $this->propertyNames[] = $propertyName;
             $getterMethod = static::getPropertyAccessor($rfl, $propertyName, array(
                 'get',
                 'is',
             ));
             $setterMethod = static::getPropertyAccessor($rfl, $propertyName, array(
                 'set',
             ));
             if ($getterMethod) {
                 $this->getters[$propertyName] = $getterMethod;
             }
             if ($setterMethod) {
                 $this->setters[$propertyName] = $setterMethod;
             }
         }
         $this->className = $className;
     }

     /**
      * @return string
      */
     public function getClassName()
     {
         return $this->className;
     }

     /**
      * @param string[]|null $propertyNames
      * @return \ReflectionMethod[]
      */
     public function getGetters($propertyNames = null)
     {
         if ($propertyNames === null) {
             return $this->getters;
         } else {
             return array_intersect_key($this->getters, array_flip($propertyNames));
         }
     }

     /**
      * @param string[]|null $propertyNames
      * @return \ReflectionMethod[]
      */
     public function getSetters($propertyNames = null)
     {
         if ($propertyNames === null) {
             return $this->setters;
         } else {
             return array_intersect_key($this->setters, array_flip($propertyNames));
         }
     }

     /**
      * @return string[]
      */
     public function getPropertyNames()
     {
         return $this->propertyNames;
     }

     /**
      * @param object $object
      * @param string[]|null $propertyNames null for all getter-accessible properties
      * @return array
      */
     public function extractProperties($object, $propertyNames)
     {
         $values = array();
         foreach ($this->getGetters($propertyNames) as $propertyName => $getter) {
             $values[$propertyName] = $getter->invoke($object);
         }
         return $values;
     }

     /**
      * @param object $object
      * @param string $propertyName
      * @return mixed
      * @throws \LogicException
      */
     public function extractProperty($object, $propertyName)
     {
         $data = $this->extractProperties($object, array($propertyName));
         if (!array_key_exists($propertyName, $data)) {
             throw new \LogicException("No getter for property {$propertyName} on " . get_class($object));
         }
         return $data[$propertyName];
     }

     /**
      * @param \ReflectionClass $class
      * @param string $fieldName
      * @param string[] $prefixes
      * @return null|\ReflectionMethod
      */
     protected static function getPropertyAccessor(\ReflectionClass $class, $fieldName, $prefixes)
     {
         $camelCasedFieldName = StringUtil::snakeToCamelCase($fieldName, true);
         foreach ($prefixes as $prefix) {
             $methodName = $prefix . $camelCasedFieldName;
             if ($class->hasMethod($methodName)) {
                 return $class->getMethod($methodName);
             }
         }
         return null;
     }
}
