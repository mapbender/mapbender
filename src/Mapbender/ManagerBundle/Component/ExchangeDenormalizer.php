<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;

/**
 *
 *
 * @author Paul Schmidt
 */
class ExchangeDenormalizer extends ExchangeSerializer implements Mapper
{

    protected $doFlush;

    protected $data;

    protected $entityPool;

    protected $classMapping = array(
        'Mapbender\WmtsBundle\Entity\TileMatrix' => 'Mapbender\WmtsBundle\Component\TileMatrix',
        'Mapbender\WmtsBundle\Entity\TileMatrixSetLink' => 'Mapbender\WmtsBundle\Component\TileMatrixSetLink',
        'Mapbender\WmtsBundle\Entity\UrlTemplateType' => 'Mapbender\WmtsBundle\Component\UrlTemplateType',
        'Mapbender\WmtsBundle\Entity\Style' => 'Mapbender\WmtsBundle\Component\Style',
    );

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
        $this->data   = $data;
        $this->entityPool = new EntityPool();
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
        if (isset($this->data[$class])) {
            foreach ($this->data[$class] as $item) {
                $found = true;
                foreach ($criteria as $key => $value) {
                    if (!isset($item[$key]) || $item[$key] !== $value) {
                        $found = false;
                        break;
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
        if ($className = $this->getClassName($data)) {
            try {
                return $this->handleEntity($className, $data);
            } catch (MappingException $e) {
                return $this->handleClass($className, $data);
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
     * @param string $className
     * @param array $data
     * @return object|null
     * @throws \Doctrine\ORM\ORMException
     * @throws MappingException
     */
    public function handleEntity($className, array $data)
    {
        $classMeta = $this->em->getClassMetadata($className);
        $identFieldNames = $classMeta->getIdentifier();
        $identValues = $this->extractFields($data, $identFieldNames);
        if ($this->isReference($data, $identValues)) {
            if ($object = $this->getImportedObject($className, $identValues)) {
                return $object;
            } elseif ($objectdata = $this->getEntityData($className, $identValues)) {
                $data = $objectdata;
            } else {
                return null;
            }
        }

        $reflectionInfo = $this->getReflectionInfo($className);
        $setters = $reflectionInfo['setters'];
        $object = new $className();
        $nonIdentifierFieldNames = array_diff($classMeta->getFieldNames(), $identFieldNames);
        foreach ($nonIdentifierFieldNames as $fieldName) {
            if (isset($data[$fieldName]) && array_key_exists($fieldName, $setters)) {
                /** @var \ReflectionMethod $setter */
                $setter = $setters[$fieldName];
                $value = $this->handleData($data[$fieldName]);
                $fm    = $classMeta->getFieldMapping($fieldName);
                if ($fm['unique']) {
                    $value =
                        EntityUtil::getUniqueValue($this->em, $classMeta->getName(), $fm['columnName'], $value, '_imp');
                }
                $setter->invoke($object, $value);
            }
        }

        $this->em->persist($object);
        if ($this->doFlush) {
            $this->em->flush();
        }
        $this->addToMapper($identValues, $object);

        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            // TODO fix add Mapbender\CoreBundle\Entity\Keyword with reference
            if (is_a($assocItem['targetEntity'], "Mapbender\CoreBundle\Entity\Keyword", true)) {
                continue;
            }
            $assocFieldName = $assocItem['fieldName'];
            if (array_key_exists($assocFieldName, $setters) && isset($data[$assocFieldName])) {
                /** @var \ReflectionMethod $setter */
                $setter = $setters[$assocFieldName];
                $result = $this->handleData($data[$assocItem['fieldName']]);
                if (is_array($result)) {
                    if (count($result)) {
                        $collection = new \Doctrine\Common\Collections\ArrayCollection($result);
                        $setter->invoke($object, $collection);
                    }
                } else {
                    $setter->invoke($object, $result);
                }
                $this->em->persist($object);
                if ($this->doFlush) {
                    $this->em->flush();
                }
            }
        }
        return $object;
    }

    public function handleClass($className, array $data)
    {
        $reflectionInfo = $this->getReflectionInfo($className);
        $object = new $className();
        foreach ($reflectionInfo['setters'] as $propertyName => $setter) {
            /** @var \ReflectionMethod $setter */
            if (isset($data[$propertyName])) {
                $value = $this->handleData($data[$propertyName]);
                if (is_array($value)) {
                    if (count($value)) {
                        $setter->invoke($object, $value);
                    }
                } else {
                    $setter->invoke($object, $value);
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
     * @param array $identData
     * @param object $object object
     */
    public function addToMapper(array $identData, $object)
    {
        $this->entityPool->add($object, $identData, false);
    }

    /**
     * @param string $class
     * @param string[] $identifier
     * @return object|null
     */
    public function getImportedObject($class, $identifier)
    {
        return $this->entityPool->get($class, $identifier);
    }

    public function getPostImportId($class, $preImportIdentifier)
    {
        $object = $this->getImportedObject($class, $preImportIdentifier);
        if ($object) {
            return implode('', $this->extractProperties($object, array('id'))) ?: null;
        }
        return null;
    }

    /**
     *
     * @inheritdoc
     */
    public function getIdentFromMapper($className, $id, $isSuperClass = false)
    {
        $postImportId = $this->getPostImportId($className, array('id' => $id));
        if (!$postImportId && $isSuperClass) {
            $uniqueClasses = $this->entityPool->getUniqueClassNames();
            foreach ($uniqueClasses as $uniqueClass) {
                if (class_exists($uniqueClass) && is_a($uniqueClass, $className, true)) {
                    $mappedId = $this->getIdentFromMapper($uniqueClass, $id, false);
                    if ($mappedId) {
                        return $mappedId;
                    }
                }
            }
        }
        return $postImportId;
    }

    /**
     * @param $data
     * @return string|null
     */
    public function getClassName($data)
    {
        if (is_array($data) && array_key_exists(self::KEY_CLASS, $data)) {
            $className = $data[self::KEY_CLASS];
            if (is_array($className)) {
                $className = $className[0];
            }
            while (!empty($this->classMapping[$className])) {
                $className = $this->classMapping[$className];
            }
            return $className;
        }
        return null;
    }
}
