<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mapbender\CoreBundle\Utils\EntityUtil;

/**
 *
 *
 * @author Paul Schmidt
 */
class ExchangeDenormalizer extends ExchangeSerializer implements Mapper
{
    /**
     *
     * @var array mapper entity id before import <-> entity id after import
     */
    protected $mapper;

    protected $doFlush;

    protected $data;

    /** @var \ReflectionMethod[][] */
    protected static $classPropertySetters = array();

    /**
     * Creates an instance.
     * @param EntityManagerInterface $em
     * @param array §data
     */
    public function __construct(EntityManagerInterface $em, array $data)
    {
        parent::__construct($em);
        $name = $em->getConnection()->getDatabasePlatform()->getName();
        $this->doFlush = $name === 'sqlite' || $name === 'mysql' || $name === 'spatialite' ? true : false;
        $this->mapper = array();
        $this->data   = $data;
    }

    public function isReference($data, array $criteria)
    {
        foreach ($data as $key => $value) {
            if (!isset($criteria[$key])) { # has other fields
                return true;
            }
        }
        return false;
    }

    public function getEntityData($class, array $criteria)
    {
        if (!is_string($class)) {
            return null;
        }
        if (isset($this->data[$class])) {
            foreach ($this->data[$class] as $item) {
                $found = true;
                foreach ($criteria as $key => $value) {
                    if (!isset($item[$key]) || $item[$key] !== $value) {
                        $found = false;
                    }
                }
                if ($found) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * @param $data
     * @return array|null|number|string|object
     * @throws \Doctrine\ORM\ORMException
     */
    public function handleData($data)
    {
        if (is_array($data) && $classDef = $this->getClassDefinition($data)) {
            try {
                $meta = $this->em->getClassMetadata($classDef[0]);
                $identFields = $this->extractFields($data, $meta->getIdentifier());
                if ($this->isReference($data, $identFields)) {
                    if ($object = $this->getAfterFromBefore($classDef[0], $identFields)) {
                        return $object['object'];
                    } elseif ($objectdata = $this->getEntityData($classDef[0], $identFields)) {
                        $object        = $this->handleEntity($objectdata, $meta);
                        return $object;
                    }
                    return null;
                } else {
                    $object        = $this->handleEntity($data, $meta);
                    return $object;
                }
            } catch (MappingException $e) {
                return $this->handleClass($data, $this->getReflectionClass($classDef[0]));
            }
        } elseif (is_array($data)) {
            $result = array();
            foreach ($data as $key => $item) {
                $result[$key] = $this->handleData($item);
            }
            return $result;
        } elseif ($data === null || is_integer($data) || is_float($data) || is_string($data) || is_bool($data)) {
            return $data;
        } else {
            return null;
        }
    }

    /**
     * @param array $data
     * @param string[] $fieldNames
     * @return array
     */
    public function extractFields(array $data, array $fieldNames)
    {
        return array_intersect_key($data, array_flip($fieldNames));
    }

    /**
     * @param array $data
     * @param ClassMetadata $classMeta
     * @return object
     * @throws \Doctrine\ORM\ORMException
     */
    public function handleEntity(array $data, ClassMetadata $classMeta)
    {
        $args   = $this->getClassConstructParams($data) ? : array();
        $reflectionClass = $classMeta->getReflectionClass();
        $object = $reflectionClass->newInstanceArgs($args);
        foreach ($classMeta->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $classMeta->getIdentifier()) && isset($data[$fieldName])
                && $setMethod = $this->getSetMethod($fieldName, $reflectionClass)) {
                $value = $this->handleData($data[$fieldName]);
                $fm    = $classMeta->getFieldMapping($fieldName);
                if ($fm['unique']) {
                    $value =
                        EntityUtil::getUniqueValue($this->em, $classMeta->getName(), $fm['columnName'], $value, '_imp');
                }
                $setMethod->invoke($object, $value);
            }
        }

        $this->em->persist($object);
        if ($this->doFlush) {
            $this->em->flush();
        }
        $this->addToMapper($object, $data, $classMeta);

        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            // TODO fix add Mapbender\CoreBundle\Entity\Keyword with reference
            if (is_a($assocItem['targetEntity'], "Mapbender\CoreBundle\Entity\Keyword", true)) {
                continue;
            }
            $setMethod = $this->getSetMethod($assocItem['fieldName'], $reflectionClass);
            if ($setMethod && isset($data[$assocItem['fieldName']])) {
                $result = $this->handleData($data[$assocItem['fieldName']]);
                if (is_array($result)) {
                    if (count($result)) {
                        $collection = new \Doctrine\Common\Collections\ArrayCollection($result);
                        $setMethod->invoke($object, $collection);
                        $this->em->persist($object);
                        if ($this->doFlush) {
                            $this->em->flush();
                        }
                    }
                } else {
                    $setMethod->invoke($object, $result);
                    $this->em->persist($object);
                    if ($this->doFlush) {
                        $this->em->flush();
                    }
                }
            }
        }
        return $object;
    }

    public function handleClass(array $data, \ReflectionClass $class)
    {
        $args = $this->getClassConstructParams($data) ? : array();
        $object               = $class->newInstanceArgs($args);
        foreach ($class->getProperties() as $property) { # only for mapbender classes
            if (isset($data[$property->getName()]) && $setMethod = $this->getSetMethod($property->getName(), $class)) {
                $value = $this->handleData($data[$property->getName()]);
                if (is_array($value)) {
                    if (count($value)) {
                        $setMethod->invoke($object, $value);
                    }
                } else {
                    $setMethod->invoke($object, $value);
                }
            }
        }
        return $object;
    }

    /**
     * Tracks an instantiated object along with the serialized data it was created from, to
     * support lookups of the instance from pre-import references -- most notably newly assigned
     * ids.
     *
     * @param object $object object
     * @param array $data from which the object was created
     * @param ClassMetaData $classMeta
     */
    public function addToMapper($object, array $data, ClassMetadata $classMeta)
    {
        $realClass = $this->getRealClass($object);
        if (!isset($this->mapper[$realClass])) {
            $this->mapper[$realClass] = array();
        }
        $criteriaBefore = $this->getIdentCriteria($data, $classMeta);
        foreach ($this->mapper[$realClass] as $mapItem) {
            if ($mapItem['before'] == $criteriaBefore) {
                return;
            }
        }
        $criteriaAfter = $this->getIdentCriteria($object, $classMeta);
        $this->mapper[$realClass][] = array(
            'before' => $criteriaBefore,
            'after' => array(
                'criteria' => $criteriaAfter,
                'object' => $object,
            ),
        );
    }

    /**
     * Returns an imported object.
     *
     * @param string $class class name
     * @param array $criteriaBefore
     * @return array|null
     */
    public function getAfterFromBefore($class, $criteriaBefore)
    {
        if (!isset($this->mapper[$class])) {
            return null;
        }

        foreach ($this->mapper[$class] as $mapItem) {
            if ($mapItem['before'] == $criteriaBefore) {
                return $mapItem['after'];
            }
        }
        return null;
    }

    /**
     * Returns an original object.
     *
     * @param string $class class name
     * @param array $criteriaAfter
     * @return array|null
     */
    public function getBeforeFromAfter($class, $criteriaAfter)
    {
        if (!isset($this->mapper[$class])) {
            return null;
        }

        foreach ($this->mapper[$class] as $mapItem) {
            if ($mapItem['after']['criteria'] == $criteriaAfter) {
                return $mapItem['before'];
            }
        }
        return null;
    }

    /**
     *
     * @inheritdoc
     */
    public function getIdentFromMapper($className, $id, $isSuperClass = false)
    {
        if ($isSuperClass) {
            foreach ($this->mapper as $key => $value) {
                if (class_exists($key) && is_a($key, $className, true)) {
                    return $this->getIdentFromMapper($key, $id, false);
                }
            }
            return null;
        } else {
            $result = $this->getAfterFromBefore($className, array('id' => $id));
            return $result && isset($result['criteria']) && isset($result['criteria']['id'])
                ? $result['criteria']['id'] : null;
        }
    }

    public function getClassName($data)
    {
        $class = $this->getClassDefinition($data);
        if (!$class) {
            return null;
        } else {
            return $class[0];
        }
    }

    public function getClassDefinition($data)
    {
        if (!$data || !is_array($data)) {
            return null;
        } elseif (key_exists(self::KEY_CLASS, $data)) {
            return $data[self::KEY_CLASS];
        } else {
            return null;
        }
    }

    public function getClassConstructParams($data)
    {
        $class = $this->getClassDefinition($data);
        if (!$class) {
            return array();
        } else {
            return $class[1];
        }
    }

    /**
     * @param $fieldName
     * @param \ReflectionClass $class
     * @return \ReflectionMethod
     */
    public function getSetMethod($fieldName, \ReflectionClass $class)
    {
        $className = $class->getName();
        if (!array_key_exists($className, static::$classPropertySetters)) {
            static::$classPropertySetters[$className] = array();
        }
        if (!array_key_exists($fieldName, static::$classPropertySetters[$className])) {
            $method = $this->getPropertyAccessor($class, $fieldName, array(
                self::KEY_SET,
                self::KEY_ADD,
            ));
            static::$classPropertySetters[$className][$fieldName] = $method;
        }
        return static::$classPropertySetters[$className][$fieldName];
    }
}
