<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Mapbender\CoreBundle\Component\Application as ApplicationComponent;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\SourceInstanceItem;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\ClassPropertiesParser;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Description of ExchangeDenormalizer
 *
 * @author paul
 */
class ExchangeDenormalizer extends ExchangeSerializer implements DenormalizerInterface
{

    /**
     *
     * @var array mapper entity id before import <-> entity id after import
     */
    protected $mapper;

    /**
     * 
     * @param ContainerInterface $container container
     * @param array $mapper mapper old id <-> new id (object)
     */
    public function __construct(ContainerInterface $container, array $mapper)
    {
        parent::__construct($container);
        $this->em = $this->container->get('doctrine')->getManager();
        $this->mapper = $mapper;
    }

    /**
     * Adds class name and primary column name into mapper.
     * 
     * @param string $class class name to add into mapper
     * @param string $idName primary column name
     */
    private function addClassToMapper($class, $idName)
    {
        if (!isset($this->mapper[$class])) {
            $this->mapper[$class] = array(
                self::KEY_PRIMARY => $idName,
                self::KEY_MAP => array()
            );
        }
    }

    /**
     * Adds ids into mappter.
     * 
     * @param string $class entity class name
     * @param int $idBefore entity id before import
     * @param int $idAfter entity id after import
     */
    private function addToMapper($class, $idBefore, $idAfter)
    {
        $this->mapper[$class][self::KEY_MAP][$idBefore] = $idAfter;
    }

    /**
     * 
     * @param string $class entity class name
     * @return type
     */
    private function getPrimary($class)
    {
        return $this->mapper[$class][self::KEY_PRIMARY];
    }

    private function getOldId($class, $idAfter)
    {
        foreach ($this->mapper[$class][self::KEY_MAP] as $idBefore_ => $idAfter_) {
            if ($idAfter === $idAfter_) {
                return $idBefore_;
            }
        }
        return null;
    }

    private function getIdAfter($class, $idBefore)
    {
        if (isset($this->mapper[$class][self::KEY_MAP][$idBefore])) {
            return $this->mapper[$class][self::KEY_MAP][$idBefore];
        } else {
            return null;
        }
    }

    private function findExistingEntity($class, $idBefore)
    {
        $idAfter = $this->getIdAfter($class, $idBefore);
        if ($idAfter !== null) {
            $criteria = array();
            $criteria[$this->getPrimary($class)] = $idAfter;
            return $this->em->getRepository($class)->findOneBy($criteria);
        } else {
            return null;
        }
    }

    private function findIdName($fields)
    {
        foreach ($fields as $fieldName => $filedProps) {
            if (isset($filedProps['Id'])) {
                return $fieldName;
            }
        }
        return null;
    }

    /**
     * 
     * @param type $data
     * @param type $class
     * @param \Mapbender\CoreBundle\Entity\Source $objectExists
     */
    public function mapSource($data, $class, $objectExists)
    {
        $reflectionClass = new \ReflectionClass($class);
        $constructorArguments = $this->getClassConstructParams($data) ? : array();
        $object = $reflectionClass->newInstanceArgs($constructorArguments);
        $fields = ClassPropertiesParser::parseFields(get_class($object));
        $idName = $this->findIdName($fields);
        if ($idName) {
            $this->addClassToMapper($class, $idName);
        }
        foreach ($fields as $fieldName => $fieldProps) {
            if (!isset($fieldProps[self::KEY_GETTER])) {
                continue;
            }
            $reflectionMethod = new \ReflectionMethod(get_class($objectExists), $fieldProps[self::KEY_GETTER]);
            $fieldValue = $reflectionMethod->invoke($objectExists);
            if ($fieldName === $idName) {
                if (get_class($objectExists) === $class && isset($fieldProps[self::KEY_GETTER])) {
                    $this->addToMapper($class, $data[$idName], $fieldValue);
                }
            } elseif (is_object($fieldValue)) {
                if ($fieldValue instanceof PersistentCollection &&
                    isset($data[$fieldName]) && is_array($data[$fieldName]) && $objectExists instanceof Source) {
                    $collection = $fieldValue->toArray();
                    for ($i = 0; $i < count($data[$fieldName]) && count($data[$fieldName]) === count($collection); $i++) {
                        $this->mapSource($data[$fieldName][$i], $this->getClassName($data[$fieldName][$i]),
                            $collection[$i]);
                    }
                } elseif ($fieldValue instanceof Contact) {
                    $this->mapSource($data[$fieldName], $this->getClassName($data[$fieldName]), $fieldValue);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $fields = ClassPropertiesParser::parseFields($class);
        $fixedField = array();
        $idName = $this->findIdName($fields);
        $object = null;
        if ($idName === null) {
            $reflectionClass = new \ReflectionClass($class);
            $constructorArguments = $this->getClassConstructParams($data) ? : array();
            $object = $reflectionClass->newInstanceArgs($constructorArguments);
        } else {
            $object = $this->findOrCreateEntity($class, $data, $fields, $idName, $fixedField);
        }
        if ($object !== null) {
            foreach ($fields as $fieldName => $fieldProps) {
                if (!isset($fieldProps[self::KEY_SETTER]) || !isset($fieldProps[self::KEY_GETTER]) ||
                    in_array($fieldName, $fixedField) || !key_exists($fieldName, $data)) {
                    continue;
                }
                $fieldValue = $data[$fieldName];
                if ($fieldValue === null) {
                    $reflectionMethod = new \ReflectionMethod($class, $fieldProps[self::KEY_SETTER]);
                    $reflectionMethod->invoke($object, null);
                } else if (is_integer($fieldValue) || is_float($fieldValue) || is_string($fieldValue) || is_bool($fieldValue)) {
                    $reflectionMethod = new \ReflectionMethod($class, $fieldProps[self::KEY_SETTER]);
                    $reflectionMethod->invoke($object, $fieldValue);
                } else if (is_array($fieldValue)) {
                    if (ArrayUtil::isAssoc($fieldValue)) {
                        $subObjectClassName = $this->getClassName($fieldValue);
                        if ($subObjectClassName) {
                            $subObject = $this->denormalize($fieldValue, $subObjectClassName);
                            if ($object instanceof SourceInstance) {
                                $this->handleSourceInstance($object, $subObject, $fieldName, $fieldValue, $fieldProps);
                            } elseif ($object instanceof SourceInstanceItem) {
                                $this->handleSourceInstanceItem($object, $subObject, $fieldName, $fieldValue,
                                    $fieldProps);
                            } else {
                                $this->handleCommon($object, $subObject, $fieldName, $fieldValue, $fieldProps);
                            }
                            unset($subObject);
                            unset($subObjectClassName);
                        } elseif ($object instanceof Element) {
                            $this->handleElement($object, $fieldName, $fieldValue, $fieldProps);
                        } elseif ($object instanceof RegionProperties) {
                            $this->handleArray($object, $fieldName, $fieldValue, $fieldProps);
                        } else {
                            $a = 0;
                        }
                    } else {
                        $getMethod = new \ReflectionMethod($class, $fieldProps[self::KEY_GETTER]);
                        $getMethodResult = $getMethod->invoke($object);
                        if ($getMethodResult !== null && $getMethodResult instanceof PersistentCollection) {
                            $this->handleArrayCollection($object, $fieldName, $fieldValue, $fieldProps);
                        } elseif ($getMethodResult !== null && is_array($getMethodResult)) {
                            $this->handleArray($object, $fieldName, $fieldValue, $fieldProps);
                        } else
                        if ($object instanceof Element) {
                            $this->handleElement($object, $fieldName, $fieldValue, $fieldProps);
                        } else {
                            $a = 0; # $fieldName configuration $object Element (all)
                        }
                        if ($getMethodResult) {
                            unset($getMethodResult);
                            unset($getMethod);
                        }
                    }
                } else {
                    $a = 0;
                }
            }
        }
        return $object;
    }

    /**
     * Finds an existing entity or creates a new entity if an antity not exists.
     * 
     * @param string $class class name
     * @param array $data data
     * @param array $fields fileds properties
     * @param array $fixedField ids from fixed entities
     * @return mixed an ORM Entity object
     */
    private function findOrCreateEntity($class, $data, $fields, $idName, &$fixedField)
    {
        $fixedField[] = $idName;
        $object = $this->findExistingEntity($class, $data[$idName]);
        if ($object === null) { # not found -> create
            $reflectionClass = new \ReflectionClass($class);
            $constructorArguments = $this->getClassConstructParams($data) ? : array();
            $object = $reflectionClass->newInstanceArgs($constructorArguments);
            foreach ($fields as $fieldName => $fieldProps) { #set not null values
                if (!isset($fieldProps['Column']) || !key_exists($fieldName, $data)) {
                    continue;
                }
                $column = $fieldProps['Column'];
                if (isset($column[self::KEY_UNIQUE]) && $column[self::KEY_UNIQUE] === 'true') {
                    $val = EntityUtil::getUniqueValue($this->em, $class, $fieldName, $data[$fieldName], '');
                    $reflectionMethod = new \ReflectionMethod($class, $fieldProps[self::KEY_SETTER]);
                    $reflectionMethod->invoke($object, $val);
                    $fixedField[] = $fieldName;
                } elseif ($fieldName !== $idName && isset($fieldProps[self::KEY_SETTER])) {
                    $exists = key_exists('nullable', $column);
                    if (!$exists || ($exists && $column['nullable'] === 'false')) {
                        if ($object instanceof Application) {
                            $val = $data[$fieldName];
                            if ($fieldName === 'template') {
                                $tmplClass = new \ReflectionClass($data[$fieldName]);
                            } elseif ($fieldName === 'updated') {
                                $val = new \DateTime();
                            }
                            $reflectionMethod = new \ReflectionMethod($class, $fieldProps[self::KEY_SETTER]);
                            $reflectionMethod->invoke($object, $val);
                            $fixedField[] = $fieldName;
                        } elseif ($data[$fieldName] !== null) {
                            $reflectionMethod = new \ReflectionMethod($class, $fieldProps[self::KEY_SETTER]);
                            $reflectionMethod->invoke($object, $data[$fieldName]);
                            $fixedField[] = $fieldName;
                        } else {
                            throw new \Exception("not null field");
                        }
                    }
                }
            }
            $this->addClassToMapper($class, $idName);
            $this->em->persist($object);
            $this->em->flush();
            $reflectionMethod = new \ReflectionMethod($class, $fields[$idName][self::KEY_GETTER]);
            $idValue = $reflectionMethod->invoke($object);
            $this->addToMapper($class, $data[$idName], $idValue);
        }
        return $object;
    }

    /**
     * Handles an Element object.
     * 
     * @param Element $object an element
     * @param string $fieldName field name
     * @param mixed $fieldValue field value
     * @param array $fieldProps field properties
     */
    private function handleElement(Element $object, $fieldName, $fieldValue, $fieldProps)
    {
        $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_SETTER]);
        $reflectionMethod->invoke($object, $fieldValue);
        $this->em->persist($object);
        $this->em->flush();
    }

    /**
     * 
     * @param type $object 
     * @param type $newObject
     * @param string $fieldName field name
     * @param mixed $fieldValue field value
     * @param array $fieldProps field properties
     */
    private function handleCommon($object, $newObject, $fieldName, $fieldValue, $fieldProps)
    {
        $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_SETTER]);
        if (EntityHandler::isEntity($this->container, $newObject)) {
            $this->em->persist($newObject);
            $this->em->flush();
        }
        $reflectionMethod->invoke($object, $newObject);
        if (EntityHandler::isEntity($this->container, $object)) {
            $this->em->persist($object);
            $this->em->flush();
        }
    }

    private function handleSourceInstance($object, $newObject, $fieldName, $fieldValue, $fieldProps)
    {
        if ($newObject instanceof Source) {
            $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_SETTER]);
            $reflectionMethod->invoke($object, $newObject);
            $this->em->persist($object);
            $this->em->flush();
        } elseif ($newObject instanceof Layerset) {
            $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_SETTER]);
            $reflectionMethod->invoke($object, $newObject);
            $this->em->persist($object);
            $this->em->flush();
        } else {
            $a = 0;
        }
    }

    private function handleSourceInstanceItem($object, $newObject, $fieldName, $fieldValue, $fieldProps)
    {
        $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_SETTER]);
        $reflectionMethod->invoke($object, $newObject);
        $this->em->persist($object);
        $this->em->flush();
    }

    private function handleArrayCollection($object, $fieldName, $fieldValue, $fieldProps)
    {
        $collection = new ArrayCollection();
        foreach ($fieldValue as $item) {
            $newclassName = $this->getClassName($item);
            if ($newclassName) {
                $newObject = $this->denormalize($item, $newclassName);
                $this->em->persist($newObject);
                if ($object instanceof Layerset && $newObject instanceof SourceInstance) {
//                    EntityHandler::createHandler($this->container, $newObject)->generateConfiguration();
                    $this->em->persist($newObject);
                }
                $this->em->flush();
                $collection->add($newObject);
            } else {
                $a = 0;
            }
        }
        $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_SETTER]);
        $reflectionMethod->invoke($object, $collection);
        $this->em->persist($object);
        $this->em->flush();
    }

    /**
     * 
     * @param type $object
     * @param type $fieldName
     * @param type $fieldValue
     * @param type $fieldProps
     */
    private function handleArray($object, $fieldName, $fieldValue, $fieldProps)
    {
        $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_SETTER]);
        $newArr = array();
        if (ArrayUtil::isAssoc($fieldValue)) {
            foreach ($fieldValue as $key => $value) {
                $newclassName = $this->getClassName($value);
                if ($newclassName) {
                    $newArr[$key] = $this->denormalize($value, $newclassName);
                } else {
                    $newArr[$key] = $value;
                }
            }
        } else {
            foreach ($fieldValue as $item) {
                $newclassName = $this->getClassName($item);
                if ($newclassName) {
                    $newArr[] = $this->denormalize($item, $newclassName);
                } else {
                    $newArr[] = $item;
                }
            }
        }
        $reflectionMethod->invoke($object, $newArr);
    }

    /**
     * Handles a configuration item.
     * 
     * @param mixed $value configuration item to handle
     * @return mixed handled item
     */
    private function handleConfiguration($value)
    {
        if (is_array($value)) {
            if (ArrayUtil::isAssoc($value)) {
                $className = $this->getClassName($value);
                if ($className) {
                    $fields = ClassPropertiesParser::parseFields($className);
                    $idName = $this->findIdName($fields);
                    $entity = $this->findExistingEntity($className, $value[$idName]);
                    $reflectionMethod = new \ReflectionMethod($className, $fields[$idName][self::KEY_GETTER]);
                    return $reflectionMethod->invoke($entity);
                } else {
                    foreach ($value as $key => $subvalue) {
                        $value[$key] = $this->handleConfiguration($subvalue);
                    }
                    return $value;
                }
            } else {
                $help = array();
                foreach ($value as $subvalue) {
                    $help[] = $this->handleConfiguration($subvalue);
                }
                return $help;
            }
        } else {
            return $value;
        }
    }

    /**
     *  Generates an element configuration.
     * 
     * @param \Mapbender\CoreBundle\Entity\Application $app
     */
    public function generateElementConfiguration(Application $app)
    {
        foreach ($app->getElements() as $element) {
            $configuration = $element->getConfiguration();
            foreach ($configuration as $key => $value) {
                $configuration[$key] = $this->handleConfiguration($value);
            }
            $elmClass = $element->getClass();
            $applComp = new ApplicationComponent($this->container, $element->getApplication(), array());
            $elmComp = new $elmClass($applComp, $this->container, $element);
            $configuration = $elmComp->denormalizeConfiguration($configuration);
            $element->setConfiguration($configuration);
            $this->em->persist($element);
            $this->em->flush();
        }
        $this->em->persist($app);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return true;
    }

}
