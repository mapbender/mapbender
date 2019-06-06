<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Mapbender\ManagerBundle\Component\Exchange\AbstractObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\ObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;

/**
 * ExchangeNormalizer class normalizes objects to array.
 *
 * @author Paul Schmidt
 */
class ExchangeNormalizer extends ExchangeHandler
{
    protected $export;

    protected $inProcess;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        gc_enable();
        parent::__construct($em);
        $em
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger(null);

        $this->export = array();
        $this->inProcess = array();
    }

    /**
     * @return array
     */
    public function getExport()
    {
        return $this->export;
    }

    /**
     * @param array   $objectData
     * @param  ClassMetadata $classMeta
     * @return bool
     */
    private function isInProcess(array $objectData, $classMeta)
    {
        $class = $classMeta->getReflectionClass()->getName();
        if (!$objectData) {
            throw new \LogicException("Empty objectdata");
        }
        if (empty($this->inProcess[$class])) {
            return false;
        }
        $idents = $classMeta->getIdentifier();
        foreach ($this->inProcess[$class] as $array) {
            $match = true;
            foreach ($idents as $ident) {
                if ($array[$ident] != $objectData[$ident]) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array  $objectData
     * @param object $classMeta
     */
    private function addInProcess(array $objectData, $classMeta)
    {
        $class = $classMeta->getReflectionClass()->getName();
        if (!isset($this->inProcess[$class])) {
            $this->inProcess[$class] = array();
        }
        $this->inProcess[$class][] = $objectData;
    }

    /**
     * @param array  $objectData
     * @param object $classMeta
     * @return bool
     */
    private function isExported(array $objectData, $classMeta)
    {
        $class = $classMeta->getReflectionClass()->getName();
        if (!isset($this->export[$class])) {
            return false;
        }
        foreach ($this->export[$class] as $array) {
            $idents = $classMeta->getIdentifier();
            $subfound = true;
            foreach ($idents as $ident) {
                $subfound = $subfound && $array[$ident] == $objectData[$ident];
            }
            if ($subfound) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array  $objectData
     * @param object $classMeta
     */
    private function addExport(array $objectData, $classMeta)
    {
        $class = $classMeta->getReflectionClass()->getName();
        if (!isset($this->export[$class])) {
            $this->export[$class] = array();
        }
        if (!$this->isExported($objectData, $classMeta)) {
            $this->export[$class][] = $objectData;
        }
    }

    /**
     * Normalizes an array.
     *
     * @param array $array array
     * @return array normalized array
     */
    private function handleArray($array)
    {
        $result = array();
        foreach ($array as $key => $item) {
            $result[$key] = $this->handleValue($item);
        }
        return $result;
    }

    /**
     * @param $value
     * @return array|string
     */
    public function handleValue($value)
    {
        if ($value === null || is_integer($value) || is_float($value) || is_string($value) || is_bool($value)) {
            return $value;
        } elseif (is_array($value)) {
            return $this->handleArray($value);
        } elseif (is_object($value)) {
            return $this->handleObject($value);
        } else {
            // why??
            return 'unsupported';
        }
    }

    /**
     * @param object $object
     * @return array
     */
    public function handleObject($object)
    {
        $entityInfo = EntityHelper::getInstance($this->em, $object);
        if ($entityInfo) {
            return $this->normalizeEntity($entityInfo, $object);
        } else {
            return $this->normalizeObject(ObjectHelper::getInstance($object), $object);
        }
    }

    /**
     * @param EntityHelper $entityInfo
     * @param object $object
     * @return array
     */
    public function normalizeEntity(EntityHelper $entityInfo, $object)
    {
        gc_enable();
        $classMeta = $entityInfo->getClassMeta();

        $identFieldNames = $classMeta->getIdentifier();
        $nonMappingFieldNames = $classMeta->getFieldNames();
        $identValues = $entityInfo->extractProperties($object, $identFieldNames);

        $referenceData = $this->createInstanceIdent($object, $identValues);
        if ($this->isInProcess($referenceData, $classMeta)) {
            return $referenceData;
        }
        $this->addInProcess($referenceData, $classMeta);

        $data = $referenceData;
        $nonIdentFieldNames = array_diff($nonMappingFieldNames, $identFieldNames);
        $nonIdentValues = $entityInfo->extractProperties($object, $nonIdentFieldNames);
        foreach ($nonIdentValues as $fieldName => $fieldValue) {
            $data[$fieldName] = $this->handleValue($fieldValue);
        }

        // No point exporting a field that doesn't have a corresponding setter.
        // This nicely avoids exporting informative relationships such as Source->getInstances
        $getters = $entityInfo->getGetters(array_keys($entityInfo->getSetters()));
        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            $fieldName = $assocItem['fieldName'];
            if (!array_key_exists($fieldName, $getters)) {
                continue;
            }

            $subObject = $getters[$fieldName]->invoke($object);
            if (!$subObject) {
                $data[$fieldName] = null;
            } elseif ($subObject instanceof PersistentCollection) {
                $data[$fieldName] = array();
                foreach ($subObject as $item) {
                    $data[$fieldName][] = $this->handleObject($item);
                }
            } else {
                $data[$fieldName] = $this->handleObject($subObject);
            }
        }
        $this->addExport($data, $classMeta);
        gc_collect_cycles();
        return $referenceData;
   }

    /**
     * @param AbstractObjectHelper $classInfo
     * @param object $object
     * @return array
     */
    public function normalizeObject(AbstractObjectHelper $classInfo, $object)
    {
        $values = $classInfo->extractProperties($object, null);
        return $this->createInstanceIdent($object, $values);
    }

    public function createInstanceIdent($object, $params = array())
    {
        return array_merge(
            array(
                self::KEY_CLASS => array(
                    ClassUtils::getClass($object),
                )
            ),
            $params
        );
    }
}
