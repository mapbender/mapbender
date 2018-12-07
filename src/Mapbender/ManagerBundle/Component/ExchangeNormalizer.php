<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Persistence\Mapping\MappingException;

/**
 * ExchangeNormalizer class normalizes objects to array.
 *
 * @author Paul Schmidt
 */
class ExchangeNormalizer extends ExchangeSerializer
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
     * @param  object $classMeta
     * @return bool
     */
    private function isInProcess(array $objectData, $classMeta)
    {
        $class = $classMeta->getReflectionClass()->getName();
        if (!isset($this->inProcess[$class])) {
            return false;
        }
        foreach ($this->inProcess[$class] as $array) {
            $idents = $classMeta->getIdentifier();
            $found = true;
            foreach ($idents as $ident) {
                $found = $found && $array[$ident] === $objectData[$ident];
            }
            if ($found) {
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
        if (!isset($this->inProcess[ $class ])) {
            $this->inProcess[ $class ] = array();
        }
        if (!$this->isInProcess($objectData, $classMeta)) {
            $this->inProcess[ $class ][] = $objectData;
        }
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
                $subfound = $subfound && $array[$ident] === $objectData[$ident];
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
            $realClass = $this->getRealClass($value);
            try {
                return $this->normalizeEntity($value, $this->em->getClassMetadata($realClass));
            } catch (MappingException $e) {
                return $this->normalizeObject($value, $this->getReflectionClass($realClass));
            }
        } else {
            // why??
            return 'unsupported';
        }
    }

    /**
     * @param object $object
     * @param ClassMetadata|null $classMeta
     * @return array
     */
    public function normalizeEntity($object, ClassMetadata $classMeta=null)
    {
        gc_enable();
        $this->em->refresh($object);
        if (!$classMeta) {
            $classMeta = $this->em->getClassMetadata($this->getRealClass($object));
        }

        $identFieldNames = $classMeta->getIdentifier();
        $refl = $classMeta->getReflectionClass();
        $identValues = $this->extractProperties($object, $identFieldNames, $refl);
        $data = $this->createInstanceIdent($object, $identValues);
        if ($this->isInProcess($data, $classMeta)) {
            return $data;
        }
        $this->addInProcess($data, $classMeta);
        $regularFields = array_diff($classMeta->getFieldNames(), $identFieldNames);
        foreach ($this->extractProperties($object, $regularFields, $refl) as $fieldName => $fieldValue) {
            $data[$fieldName] = $this->handleValue($fieldValue);
        }

        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            $fieldName = $assocItem['fieldName'];
            if ($getMethod = $this->getReturnMethod($fieldName, $refl)) {
                $subObject = $getMethod->invoke($object);
                if (!$subObject) {
                    $data[$fieldName] = null;
                } elseif ($subObject instanceof PersistentCollection) {
                    $data[$fieldName] = array();
                    foreach ($subObject as $item) {
                        $data[$fieldName][] = $this->normalizeEntity($item);
                    }
                } else {
                    $data[$fieldName] = $this->normalizeEntity($subObject);
                }
            }
        }
        $this->addExport($data, $classMeta);
        gc_collect_cycles();
        return $data;
    }

    public function normalizeObject($object, \ReflectionClass $class)
    {
        $params = $this->extractProperties($object, $this->getPropertyNames($class), $class);
        return $this->createInstanceIdent($object, $params);
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
}
