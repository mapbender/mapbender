<?php
namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\ManagerBundle\Component\Exchange\AbstractObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\Exchange\ObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;

/**
 *
 *
 * @author Paul Schmidt
 */
class ExchangeDenormalizer extends ExchangeHandler
{

    public function isReference($data, array $criteria)
    {
        return !array_diff_key($criteria, $data);
    }

    /**
     * @param ImportState $state
     * @param mixed $data
     * @return array|null|number|string|object
     * @throws \Doctrine\ORM\ORMException
     */
    public function handleData(ImportState $state, $data)
    {
        if ($className = $this->extractClassName($data)) {
            if ($entityInfo = EntityHelper::getInstance($this->em, $className)) {
                $identValues = $this->extractArrayFields($data, $entityInfo->getClassMeta()->getIdentifier());
                if ($this->isReference($data, $identValues)) {
                    if ($object = $state->getEntityPool()->get($className, $identValues)) {
                        return $object;
                    } elseif ($objectData = $state->getEntityData($className, $identValues)) {
                        return $this->handleEntity($state, $entityInfo, $objectData);
                    } else {
                        return null;
                    }
                } else {
                    return $this->handleEntity($state, $entityInfo, $data);
                }
            } else {
                $classInfo = ObjectHelper::getInstance($className);
                return $this->handleClass($state, $classInfo, $data);
            }
        } elseif (is_array($data)) {
            $result = array();
            foreach ($data as $key => $item) {
                $result[$key] = $this->handleData($state, $item);
            }
            return $result;
        } elseif ($data === null || is_integer($data) || is_float($data) || is_string($data) || is_bool($data)) {
            return $data;
        } else {
            return null;
        }
    }

    /**
     * @param ImportState $state
     * @param EntityHelper $entityInfo
     * @param array $data
     * @return object|null
     * @throws \Doctrine\ORM\ORMException
     */
    protected function handleEntity(ImportState $state, EntityHelper $entityInfo, array $data)
    {
        $classMeta = $entityInfo->getClassMeta();
        $className = $classMeta->getName();
        $identFieldNames = $classMeta->getIdentifier();

        $setters = $entityInfo->getSetters();
        $object = new $className();
        $nonIdentifierFieldNames = array_diff($classMeta->getFieldNames(), $identFieldNames);
        foreach ($nonIdentifierFieldNames as $fieldName) {
            if (isset($data[$fieldName]) && array_key_exists($fieldName, $setters)) {
                $setter = $setters[$fieldName];
                $value = $this->handleData($state, $data[$fieldName]);
                $fm    = $classMeta->getFieldMapping($fieldName);
                if ($fm['unique']) {
                    $value =
                        EntityUtil::getUniqueValue($this->em, $classMeta->getName(), $fm['columnName'], $value, '_imp');
                }
                $setter->invoke($object, $value);
            }
        }

        $this->em->persist($object);
        $state->getEntityPool()->add($object, $this->extractArrayFields($data, $identFieldNames));

        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            if ($this->isEntityClassBlacklisted($assocItem['targetEntity'])) {
                continue;
            }
            $assocFieldName = $assocItem['fieldName'];
            if (array_key_exists($assocFieldName, $setters) && isset($data[$assocFieldName])) {
                $setter = $setters[$assocFieldName];
                $result = $this->handleData($state, $data[$assocItem['fieldName']]);
                if (is_array($result)) {
                    if (count($result)) {
                        $collection = new \Doctrine\Common\Collections\ArrayCollection($result);
                        $setter->invoke($object, $collection);
                    }
                } else {
                    $setter->invoke($object, $result);
                }
                $this->em->persist($object);
            }
        }
        return $object;
    }

    /**
     * @param ImportState $state
     * @param AbstractObjectHelper $classInfo
     * @param array $data
     * @return object
     * @throws \Doctrine\ORM\ORMException
     */
    protected function handleClass(ImportState $state, AbstractObjectHelper $classInfo, array $data)
    {
        $className = $classInfo->getClassName();
        $object = new $className();
        foreach ($classInfo->getSetters(array_keys($data)) as $propertyName => $setter) {
            if ($data[$propertyName] !== null) {
                $value = $this->handleData($state, $data[$propertyName]);
                if (!is_array($value) || count($value)) {
                    $setter->invoke($object, $value);
                }
            }
        }
        return $object;
    }
}
