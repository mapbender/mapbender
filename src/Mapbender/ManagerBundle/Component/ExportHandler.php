<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Component\Exception\UnpersistedEntity;
use Mapbender\ManagerBundle\Component\Exchange\AbstractObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\ManagerBundle\Component\Exchange\ExportDataPool;
use Mapbender\ManagerBundle\Component\Exchange\ObjectHelper;

/**
 * @author Paul Schmidt
 */
class ExportHandler extends ExchangeHandler
{
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
    }

    /**
     * @param Application $application
     * @return array
     */
    public function exportApplication(Application $application)
    {
        $entityBuffer = new ExportDataPool();
        gc_enable();
        $time = array(
            'start' => microtime(true)
        );
        foreach ($this->getApplicationSourceInstances($application) as $source) {
            $this->handleValue($entityBuffer, $source);
            gc_collect_cycles();
        }
        $time['sources'] = microtime(true);
        $time['sources'] = $time['sources'] . '/' . ($time['sources'] - $time['start']);

        $this->handleValue($entityBuffer, $application);
        gc_collect_cycles();
        $time['end'] = microtime(true);
        $time['total'] = $time['end'] - $time['start'];
        gc_collect_cycles();
        $export = $entityBuffer->getAllGroupedByClassName();

        $export['time'] = $time;
        return $export;
    }

    /**
     * Get current user allowed application sources
     *
     * @param Application $app
     * @return SourceInstance[]
     */
    protected function getApplicationSourceInstances(Application $app)
    {
        $instanceIds = array();
        $instances = array();
        foreach ($app->getLayersets() as $layerSet) {
            foreach ($layerSet->getInstances() as $instance) {
                $instanceId = $instance->getId();
                if (!in_array($instanceId, $instanceIds)) {
                    $instanceIds[] = $instanceId;
                    $instances[] = $instance;
                }
            }
        }
        return $instances;
    }

    /**
     * Normalizes an array.
     *
     * @param ExportDataPool $exportPool
     * @param array $array
     * @return array normalized array
     */
    private function handleArray(ExportDataPool $exportPool, $array)
    {
        $result = array();
        foreach ($array as $key => $item) {
            $result[$key] = $this->handleValue($exportPool, $item);
        }
        return $result;
    }

    /**
     * @param ExportDataPool $exportPool
     * @param mixed $value
     * @return array|string
     */
    public function handleValue(ExportDataPool $exportPool, $value)
    {
        if ($value === null || is_integer($value) || is_float($value) || is_string($value) || is_bool($value)) {
            return $value;
        } elseif (is_array($value)) {
            return $this->handleArray($exportPool, $value);
        } elseif (is_object($value)) {
            return $this->handleObject($exportPool, $value);
        } else {
            // why??
            return 'unsupported';
        }
    }

    /**
     * @param ExportDataPool $exportPool
     * @param object $object
     * @return array
     */
    public function handleObject(ExportDataPool $exportPool, $object)
    {
        $entityInfo = EntityHelper::getInstance($this->em, $object);
        if ($entityInfo) {
            return $this->normalizeEntity($exportPool, $entityInfo, $object);
        } else {
            return $this->normalizeObject(ObjectHelper::getInstance($object), $object);
        }
    }

    /**
     * @param ExportDataPool $exportPool
     * @param EntityHelper $entityInfo
     * @param object $object
     * @return array
     */
    public function normalizeEntity(ExportDataPool $exportPool, EntityHelper $entityInfo, $object)
    {
        gc_enable();
        $classMeta = $entityInfo->getClassMeta();

        $identFieldNames = $classMeta->getIdentifier();
        $nonMappingFieldNames = $classMeta->getFieldNames();
        $identValues = $entityInfo->extractProperties($object, $identFieldNames);
        if ($identValues === array_fill_keys($identFieldNames, null)) {
            throw new UnpersistedEntity();
        }

        $referenceData = $this->createInstanceIdent($object, $identValues);
        // Try to store some dummy data in the export to mark the entity as 'started processing'
        if (!$exportPool->addEntry($classMeta->getName(), $identValues, true, false)) {
            // Already exported or marked as started => return backreference data only
            return $referenceData;
        }

        $data = $referenceData;
        $nonIdentFieldNames = array_diff($nonMappingFieldNames, $identFieldNames);
        $nonIdentValues = $entityInfo->extractProperties($object, $nonIdentFieldNames);
        foreach ($nonIdentValues as $fieldName => $fieldValue) {
            $data[$fieldName] = $this->handleValue($exportPool, $fieldValue);
        }

        // No point exporting a field that doesn't have a corresponding setter.
        // This nicely avoids exporting informative relationships such as Source->getInstances
        $getters = $entityInfo->getGetters(array_keys($entityInfo->getSetters()));
        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            if ($this->isEntityClassBlacklisted($assocItem['targetEntity'])) {
                continue;
            }
            $fieldName = $assocItem['fieldName'];
            if (!array_key_exists($fieldName, $getters)) {
                continue;
            }

            $subObject = $getters[$fieldName]->invoke($object);
            if (!$subObject) {
                $data[$fieldName] = null;
            } elseif ($subObject instanceof Collection) {
                $data[$fieldName] = array();
                foreach ($subObject as $item) {
                    $data[$fieldName][] = $this->handleObject($exportPool, $item);
                }
            } else {
                try {
                    $data[$fieldName] = $this->handleObject($exportPool, $subObject);
                } catch (UnpersistedEntity $e) {
                    // ignore
                }
            }
        }

        // replace dummy data with full export value
        $exportPool->addEntry($classMeta->getName(), $identValues, $data, true);
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
        foreach ($values as $key => $value) {
            // also handle nested non-Entity objects
            if (is_object($value)) {
                $values[$key] = $this->normalizeObject(ObjectHelper::getInstance($value), $value);
            }
        }
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
