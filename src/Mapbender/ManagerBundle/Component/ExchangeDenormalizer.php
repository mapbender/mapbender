<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mapbender\CoreBundle\Component\Application as ApplicationComponent;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of ExchangeDenormalizer
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

    /**
     * Creates an instance.
     * @param ContainerInterface $container container
     * @param array $mapper mapper old id <-> new id (object)
     */
    public function __construct(ContainerInterface $container, array $mapper, array $data)
    {
        parent::__construct($container);
        $this->em     = $this->container->get('doctrine')->getManager();
        $name = $this->em->getConnection()->getDatabasePlatform()->getName();
        $this->doFlush = $name === 'sqlite' || $name === 'mysql' || $name === 'spatialite' ? true : false;
        $this->mapper = $mapper;
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

    public function findEntities($class, array $criteria)
    {
        return $this->em->getRepository($class)->findBy($criteria);
    }

    public function handleData($data)
    {
        if (is_array($data) && $classDef = $this->getClassDifinition($data)) {
            try {
                $this->em->getRepository($classDef[0]);
                $meta     = $this->getClassMetadata($classDef[0]);
                $criteria = $this->getIdentCriteria($data, $meta);
                if ($this->isReference($data, $criteria)) {
                    if ($object = $this->getAfterFromBefore($classDef[0], $criteria)) {
                        return $object['object'];
                    } elseif ($objectdata = $this->getEntityData($classDef[0], $criteria)) {
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

    private function saveEntity($object, ClassMetadata $classMeta, $criteriaBefore)
    {
        $this->em->persist($object);
        if ($this->doFlush) {
            $this->em->flush();
        }
        $criteriaAfter = $this->getIdentCriteria($object, $classMeta);
        $this->addToMapper($object, $criteriaBefore, $criteriaAfter);
    }

    public function handleEntity(array $data, ClassMetadata $classMeta)
    {
        $criteriaBefore = $this->getIdentCriteria($data, $classMeta);
        $args   = $this->getClassConstructParams($data) ? : array();
        $object = $classMeta->getReflectionClass()->newInstanceArgs($args);
        foreach ($classMeta->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $classMeta->getIdentifier()) && isset($data[$fieldName])
                && $setMethod = $this->getSetMethod($fieldName, $classMeta->getReflectionClass())) {
                $value = $this->handleData($data[$fieldName]);
                $fm    = $classMeta->getFieldMapping($fieldName);
                if ($fm['unique']) {
                    $value =
                        EntityUtil::getUniqueValue($this->em, $classMeta->getName(), $fm['columnName'], $value, '_imp');
                }
                $setMethod->invoke($object, $value);
            }
        }
        $this->saveEntity($object, $classMeta, $criteriaBefore);
        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            $hasJoinColumns = isset($assocItem['joinColumns']);
            $hasFieldName = isset($data[$assocItem['fieldName']]);
            // TODO fix add Mapbender\CoreBundle\Entity\Keyword with reference
            if (isset($data[$assocItem['fieldName']])
                && ($setMethod = $this->getSetMethod($assocItem['fieldName'], $classMeta->getReflectionClass()))
                && !$this->findSuperClass($assocItem['targetEntity'], "Mapbender\CoreBundle\Entity\Keyword")) {
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
                    $a = 0;
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
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return $this->handleData($data);
    }

    /**
     * Adds criteria to mapper.
     *
     * @param mixed $object object
     * @param array $criteriaBefore criteria from data
     * @param array $criteriaAfter criteria after save
     * @return type
     */
    public function addToMapper($object, array $criteriaBefore, array $criteriaAfter)
    {
        $realClass = $this->getRealClass($object);
        if (!isset($this->mapper[$realClass])) {
            $this->mapper[$realClass] = array();
        }
        foreach ($this->mapper[$realClass] as $mapItem) {
            if ($mapItem['before'] == $criteriaBefore) {
                return;
            }
        }
        $this->mapper[$realClass][] =
            array('before' => $criteriaBefore, 'after' => array('criteria' => $criteriaAfter, 'object' => $object));
    }

    /**
     * Returns an imported object.
     *
     * @param string $class class name
     * @param int $criteriaBefore entity id before import
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
     * @param int $criteriaAfter entity id after import
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
                foreach ($value as $key => $subvalue) {
                    $help[$key] = $this->handleConfiguration($subvalue);
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
            $elmClass = $element->getClass();
            $applComp = new ApplicationComponent($this->container, $element->getApplication());
            $elmComp = new $elmClass($applComp, $this->container, $element);
            $configuration = $element->getConfiguration();
            foreach ($configuration as $key => $value) {
                if ($key === 'target') { # dirty
                    $target = $this->getAfterFromBefore($this->getRealClass($element), array('id' => $value));
                    $configuration[$key] = $target['criteria']['id'];
                } else {
                    $configuration[$key] = $this->handleConfiguration($value);
                }
            }
            $configuration = $elmComp->denormalizeConfiguration($configuration, $this);
            $element->setConfiguration($configuration);
            $this->em->persist($element);
            $this->em->flush();
        }
        $this->em->persist($app);
        $this->em->flush();
    }

    /**
     *
     * @inheritdoc
     */
    public function getIdentFromMapper($className, $id, $isSuperClass = false)
    {
        if ($isSuperClass) {
            foreach ($this->mapper as $key => $value) {
                if (class_exists($key) && $this->findSuperClass($key, $className)) {
                    $result = $this->getAfterFromBefore($key, array('id' => $id));
                    if ($result && isset($result['criteria']) && isset($result['criteria']['id'])) {
                        return $result['criteria']['id'];
                    }
                }
            }
            return null;
        } else {
            $result = $this->getAfterFromBefore($className, array('id' => $id));
            return $result && isset($result['criteria']) && isset($result['criteria']['id'])
                ? $result['criteria']['id'] : null;
        }
    }
}
