<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mapbender\CoreBundle\Utils\EntityAnnotationParser as EAP;

/**
 * ExchangeNormalizer class normalizes objects to array.
 *
 * @author Paul Schmidt
 */
abstract class ExchangeSerializer
{
    const KEY_CLASS         = '__class__';
    const KEY_SLUG          = 'slug';
    const KEY_IDENTIFIER    = 'identifier';
    const KEY_GETTER        = EAP::GETTER;
    const KEY_SETTER        = EAP::SETTER;
    const KEY_COLUMN        = EAP::COLUMN;
    const KEY_UNIQUE        = 'unique';
    const KEY_MAP           = 'map';
    const KEY_PRIMARY       = 'primary';
    const KEY_CONFIGURATION = 'configuration';
    const KEY_GET = 'get';
    const KEY_SET = 'set';
    const KEY_ADD = 'add';
    const KEY_HAS = 'has';
    const KEY_IS  = 'is';

    /** @var EntityManagerInterface */
    protected $em;

    protected static $reflectionInfo = array();

    /** @var \ReflectionClass[] */
    protected static $classReflection = array();

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
                    static::KEY_HAS,
                ));
                $setterMethod = static::getPropertyAccessor($rfl, $propertyName, array(
                    self::KEY_SET,
                    self::KEY_ADD,
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
     * @param $realClass
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    protected static function getReflectionClass($realClass)
    {
        if (!isset(static::$classReflection[$realClass])) {
            static::$classReflection[$realClass] = new \ReflectionClass($realClass);
        }
        return static::$classReflection[$realClass];
    }

    /**
     * @param object $object
     * @param string $fieldName
     * @return null|\ReflectionMethod
     */
    public function getReturnMethod($object, $fieldName)
    {
        $rfi = $this->getReflectionInfo(get_class($object));
        if (array_key_exists($fieldName, $rfi['getters'])) {
            return $rfi['getters'][$fieldName];
        } else {
            return null;
        }
    }

    /**
     * @param \ReflectionClass $class
     * @param string $fieldName
     * @param string[] $prefixes
     * @return null|\ReflectionMethod
     */
    protected static function getPropertyAccessor(\ReflectionClass $class, $fieldName, $prefixes)
    {
        $methodHash = "";
        foreach (preg_split("/_/", $fieldName) as $chunk) {
            $chunk = ucwords($chunk);
            $methodHash .= $chunk;
        }
        foreach ($prefixes as $prefix) {
            if ($class->hasMethod($prefix . $methodHash)) {
                return $class->getMethod($prefix . $methodHash);
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
     * @param ClassMetadata $meta
     * @param string[] $extra
     * @return string[]
     */
    public function collectEntityFieldNames(ClassMetadata $meta, $extra = array())
    {
        $fieldNames = $extra;
        foreach ($meta->getFieldNames() as $fieldName) {
            if ($meta->isUniqueField($fieldName)) {
                $fieldNames[] = $fieldName;
            }
        }
        // make sure to remove ident fields, even if included in uniques or $extra
        return array_unique(array_diff($fieldNames, $meta->getIdentifier()));
    }
}
