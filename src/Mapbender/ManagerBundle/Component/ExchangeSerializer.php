<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\StringUtil;

/**
 * ExchangeNormalizer class normalizes objects to array.
 *
 * @author Paul Schmidt
 */
abstract class ExchangeSerializer
{
    const KEY_CLASS         = '__class__';
    const KEY_GET = 'get';
    const KEY_SET = 'set';
    const KEY_ADD = 'add';
    const KEY_HAS = 'has';
    const KEY_IS  = 'is';

    /** @var EntityManagerInterface */
    protected $em;

    protected static $reflectionInfo = array();

    /**
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $className
     * @return array
     */
    protected static function getReflectionInfo($className)
    {
        if (!array_key_exists($className, static::$reflectionInfo)) {
            $rfl = new \ReflectionClass($className);
            $propertyNames = array();
            $getters = array();
            $setters = array();
            foreach ($rfl->getProperties() as $prop) {
                $propertyName = $prop->getName();
                $propertyNames[] = $propertyName;
                $getterMethod = static::getPropertyAccessor($rfl, $propertyName, array(
                    static::KEY_GET,
                    static::KEY_IS,
                ));
                $setterMethod = static::getPropertyAccessor($rfl, $propertyName, array(
                    self::KEY_SET,
                ));
                if ($getterMethod) {
                    $getters[$propertyName] = $getterMethod;
                }
                if ($setterMethod) {
                    $setters[$propertyName] = $setterMethod;
                }
            }
            static::$reflectionInfo[$className] = array(
                'propertyNames' => $propertyNames,
                'getters' => $getters,
                'setters' => $setters,
            );
        }
        return static::$reflectionInfo[$className];
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

    /**
     * @param object $object
     * @param string[]|null $propertyNames null for all getter-accessible properties
     * @return array
     */
    public function extractProperties($object, $propertyNames)
    {
        $rfi = $this->getReflectionInfo(get_class($object));
        if ($propertyNames === null) {
            $getters = $rfi['getters'];
        } else {
            $getters = array_intersect_key($rfi['getters'], array_flip($propertyNames));
        }
        $values = array();
        foreach ($getters as $propertyName => $getter) {
            /** @var \ReflectionMethod $getter */
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
}
