<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mapbender\CoreBundle\Utils\EntityAnnotationParser as EAP;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    protected $container;

    protected $classMetadata;

    protected $classReflection;

    /**
     *
     * @param ContainerInterface $container container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->classMetadata = array();
        $this->classReflection = array();
    }

    public function getClassMetadata($realClass)
    {
        if(!isset($this->classMetadata[$realClass])) {
            $this->classMetadata[$realClass] = $this->em->getClassMetadata($realClass);
        }
        return $this->classMetadata[$realClass];
    }

    public function getReflectionClass($realClass)
    {
        if(!isset($this->classReflection[$realClass])) {
            $this->classReflection[$realClass] = new \ReflectionClass($realClass);
        }
        return $this->classReflection[$realClass];
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    public function getParentClasses($class, $parents = array())
    {
        $parent = get_parent_class($class);
        if ($parent) {
            $plist[$parent] = $parent;
            $plist          = $this->getParentClasses($parent, $plist);
        }
    }

    public function getReturnMethod($fieldName, \ReflectionClass $class)
    {
        $method = null;
        if ($method = $this->getMethodName($fieldName, self::KEY_GET, $class)) {
            return $method;
        } elseif ($method = $this->getMethodName($fieldName, self::KEY_IS, $class)) {
            return $method;
        } elseif ($method = $this->getMethodName($fieldName, self::KEY_HAS, $class)) {
            return $method;
        }
    }

    public function getSetMethod($fieldName, \ReflectionClass $class)
    {
        $method = null;
        if ($method = $this->getMethodName($fieldName, self::KEY_SET, $class)) {
            return $method;
        } elseif ($method = $this->getMethodName($fieldName, self::KEY_ADD, $class)) {
            return $method;
        }
    }

    public function getMethodName($fieldName, $prefix, \ReflectionClass $class)
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

    public function createRealObject($object)
    {
        $objClass        = $this->getRealClass($object);
        $reflectionClass = $this->getReflectionClass($objClass);
        if (!$reflectionClass->getConstructor()) {
            return $reflectionClass->newInstanceArgs(array());
        } elseif (count($reflectionClass->getConstructor()->getParameters()) === 0) {
            return $reflectionClass->newInstanceArgs(array());
        } else {
            #count($reflectionClass->getConstructor()->getParameters()) > 0
            # TODO ???
            return $reflectionClass->newInstanceArgs(array());
        }
    }

    public function createInstanceIdent($object, $params = array())
    {
        return array_merge(
            array(
                self::KEY_CLASS => array(
                    $this->getRealClass($object),
                    array()
                )
            ),
            $params
        );
    }

    /**
     * Creates a list of key value pairs for unique search of entities.
     * @param mixed $data entity object or serialized entity object (array)
     * @param \Mapbender\ManagerBundle\Component\ClassMetadata $meta
     * @param boolean $addUniques flag to add of uniques fields
     * @param array $added a list of added fields
     * @return array list of search parameters (criteria)
     */
    public function getIdentCriteria($data, ClassMetadata $meta, $addUniques = false, array $added = array())
    {
        if (is_array($data)) {
            return $this->criteriaFromData($data, $meta, $addUniques, $added);
        } elseif (is_object($data)) {
            return $this->criteriaFromObject($data, $meta, $addUniques, $added);
        } else {
            return null;
        }
    }

    /**
     * Creates a list of key value pairs for unique search of entities.
     * @param array $data serialized entity
     * @param \Mapbender\ManagerBundle\Component\ClassMetadata $meta
     * @param boolean $addUniques flag to add of uniques fields
     * @param array $added a list of added fields
     * @return array list of search parameters (criteria)
     */
    private function criteriaFromData(array $data, ClassMetadata $meta, $addUniques = false, array $added = array())
    {
        $criteria = array();
        $idents   = $meta->getIdentifier();
        foreach ($idents as $ident) {
            $criteria[$ident] = $data[$ident];
        }
        if ($addUniques) {
            $fieldNames = $meta->getFieldNames();
            foreach ($fieldNames as $fieldName) {
                $fm = $meta->getFieldMapping($fieldName);
                if ($fm['unique'] && isset($data[$fieldName])) {
                    $criteria[$fieldName] = $data[$fieldName];
                }
            }
        }
        foreach ($added as $addFieldName) {
            if (!isset($criteria[$addFieldName]) && isset($data[$addFieldName])) {
                $criteria[$addFieldName] = $data[$addFieldName];
            }
        }
        return $criteria;
    }

    private function criteriaFromObject($object, ClassMetadata $meta, $addUniques = false, array $added = array())
    {
        $criteria = array();
        $idents = $meta->getIdentifier();
        foreach ($idents as $ident) {
            if ($getMethod = $this->getReturnMethod($ident, $meta->getReflectionClass())) {
                $criteria[$ident] = $getMethod->invoke($object);
            }
        }
        if ($addUniques) {
            $fieldNames = $meta->getFieldNames();
            foreach ($fieldNames as $fieldName) {
                $fm = $meta->getFieldMapping($fieldName);
                if ($fm['unique'] && $getMethod = $this->getReturnMethod($ident, $meta->getReflectionClass())) {
                    $criteria[$fieldName] = $getMethod->invoke($object);
                }
            }
        }
        foreach ($added as $addFieldName) {
            if (!isset($criteria[$addFieldName])
                && $getMethod = $this->getReturnMethod($ident, $meta->getReflectionClass())) {
                $criteria[$addFieldName] = $getMethod->invoke($object);
            }
        }
        return $criteria;
    }

    public function getClassName($data)
    {
        $class = $this->getClassDifinition($data);
        if (!$class) {
            return null;
        } else {
            return $class[0];
        }
    }

    public function getClassDifinition($data)
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
        $class = $this->getClassDifinition($data);
        if (!$class) {
            return array();
        } else {
            return $class[1];
        }
    }

    /**
     * Checks if given class or it parent is a class to find.
     * @param type $classIs
     * @param type $classToFind
     * @return boolean
     */
    public function findSuperClass($classIs, $classToFind)
    {
        if ($classIs === $classToFind) {
            return true;
        } elseif ($super = get_parent_class($classIs)) {
            return $this->findSuperClass($super, $classToFind);
        } else {
            return false;
        }
    }
}
