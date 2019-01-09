<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Util\ClassUtils;
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

    /** @var \ReflectionClass[] */
    protected static $classReflection = array();
    /** @var string[] */
    protected static $classPropertyNames = array();
    /** @var \ReflectionMethod[][] */
    protected static $classPropertyGetters = array();

    /**
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
     * @param \ReflectionClass $refl
     * @return string[]
     */
    protected static function getPropertyNames(\ReflectionClass $refl)
    {
        $className = $refl->getName();
        if (!isset(static::$classPropertyNames[$className])) {
            $propNames = array();
            foreach ($refl->getProperties() as $prop) {
                $propNames[] = $prop->getName();
            }
            static::$classPropertyNames[$className] = $propNames;
        }
        return static::$classPropertyNames[$className];
    }

    /**
     * @param $fieldName
     * @param \ReflectionClass $class
     * @return null|\ReflectionMethod
     */
    public function getReturnMethod($fieldName, \ReflectionClass $class)
    {
        $className = $class->getName();
        if (!array_key_exists($className, static::$classPropertyGetters)) {
            static::$classPropertyGetters[$className] = array();
        }
        if (!array_key_exists($fieldName, static::$classPropertyGetters[$className])) {
            $method = $this->getPropertyAccessor($class, $fieldName, array(
                self::KEY_GET,
                self::KEY_IS,
                self::KEY_HAS,
            ));
            static::$classPropertyGetters[$className][$fieldName] = $method;
        }
        return static::$classPropertyGetters[$className][$fieldName];
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

    public function getRealClass($object)
    {
        $objClass = "";
        if (is_object($object)) {
            $objClass = ClassUtils::getClass($object);
        } elseif (is_string($object)) {
            $objClass = ClassUtils::getRealClass($object);
        }
        return $objClass;
    }

    /**
     * Creates a list of key value pairs for unique search of entities.
     * @param mixed $data entity object or serialized entity object (array)
     * @param ClassMetadata $meta
     * @return array|null
     */
    public function getIdentCriteria($data, ClassMetadata $meta)
    {
        $identFields = $meta->getIdentifier();
        if (is_array($data)) {
            return array_intersect_key($data, array_flip($identFields));
        } elseif (is_object($data)) {
            return $this->extractProperties($data, $identFields, $meta->getReflectionClass());
        } else {
            // why??
            return null;
        }
    }

    /**
     * @param object $object
     * @param string[] $propertyNames
     * @param \ReflectionClass $refl
     * @return array
     */
    protected function extractProperties($object, $propertyNames, \ReflectionClass $refl)
    {
        $values = array();
        foreach ($propertyNames as $propertyName) {
            if ($getMethod = $this->getReturnMethod($propertyName, $refl)) {
                $values[$propertyName] = $getMethod->invoke($object);
            }
        }
        return $values;
    }

    /**
     * @param ClassMetadata $meta
     * @param bool $includeIdent
     * @param bool $includeUniques
     * @param string[] $extra
     * @return string[]
     */
    public function collectEntityFieldNames(ClassMetadata $meta, $includeIdent, $includeUniques, $extra = array())
    {
        $fieldNames = $extra;
        if ($includeUniques) {
            foreach ($meta->getFieldNames() as $fieldName) {
                if ($meta->isUniqueField($fieldName)) {
                    $fieldNames[] = $fieldName;
                }
            }
        }
        if ($includeIdent) {
            // put ident fields first
            return array_unique(array_merge($meta->getIdentifier(), $fieldNames));
        } else {
            // make sure to remove ident fields, even if included in uniques or $extra
            return array_unique(array_diff($fieldNames, $meta->getIdentifier()));
        }
    }
}
