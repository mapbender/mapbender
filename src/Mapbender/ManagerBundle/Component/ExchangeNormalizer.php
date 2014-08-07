<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Component\SourceItem;
use Mapbender\CoreBundle\Component\SourceInstanceItem;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ClassPropertiesParser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * ExchangeNormalizer class normalizes objects to array.
 *
 * @author Paul Schmidt
 */
class ExchangeNormalizer implements NormalizerInterface
{

    private function getParentClasses($class, $parents = array())
    {
        $parent = get_parent_class($class);
        if ($parent) {
            $plist[$parent] = $parent;
            $plist = $this->getParentClasses($parent, $plist);
        }
    }

    private function createRealObject($object)
    {
        $objClass = "";
        if (is_object($object)) {
            $objClass = ClassUtils::getClass($object);
        } elseif (is_string($object)) {
            $objClass = ClassUtils::getRealClass($object);
        }
        $reflectionClass = new \ReflectionClass($objClass);
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

    private function createInstanceIdent($object, $params = array())
    {
        return array_merge(
            array(
            '__class__' => array(
                get_class($object),
                array())
            ), $params
        );
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$this->supportsNormalization($object, $format)) {
            throw new \Exception("Object not supported for normalization");
        }
        $realObj = $this->createRealObject($object);
        $data = $this->createInstanceIdent($realObj);
        $fields = ClassPropertiesParser::parseFields(get_class($object));
        foreach ($fields as $fieldName => $filedProps) {
            if (!isset($filedProps['getter']) ||
                ($realObj instanceof SourceInstance && $fieldName === 'configuration')) {
                continue;
            }
            $reflectionMethod = new \ReflectionMethod(get_class($object), $filedProps['getter']);
            $filedValue = $reflectionMethod->invoke($object);
            if($fieldName === 'sublayer'){
                $a = 0;
            }
            if ($filedValue === null) {
                $data[$fieldName] = $filedValue;
            } else if (is_integer($filedValue) || is_float($filedValue) || is_string($filedValue) || is_bool($filedValue)) {
                $data[$fieldName] = $filedValue;
            } else if (is_array($filedValue)) {
                $data[$fieldName] = $filedValue;
            } else if (is_object($filedValue)) {
                if ($filedValue instanceof PersistentCollection) {
                    $data[$fieldName] = $this->handlePersistentCollection($fieldName, $realObj, $filedValue);
                } else {
                    $realValObj = $this->createRealObject($filedValue);
                    if ($realObj instanceof SourceItem) {
                        $data[$fieldName] = $this->handleSourceItem($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof Source) {
                        $data[$fieldName] = $this->handleSource($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof Application) {
                        $data[$fieldName] = $this->handleApplication($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof Layerset) {
                        $data[$fieldName] = $this->handleLayerset($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof SourceInstance) {
                        $data[$fieldName] = $this->handleSourceInstance($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof SourceInstanceItem) {
                        $data[$fieldName] = $this->handleSourceInstanceItem($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof Element) {
                        $data[$fieldName] = $this->handleElement($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof RegionProperties) {
                        $data[$fieldName] = $this->handleRegionProperties($fieldName, $filedValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof \DateTime) {
//                        $a = 0;
//                        $this->handleRegionProperties($fieldName, $filedValue, $realObj, $realValObj);
                    } else {
                        $data[$fieldName] = $this->normalize($filedValue);
                    }
                }
            } else {
                $data[$fieldName] = 'unsupported';
            }
        }
        return $data;
    }

    /**
     * Handles Application to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleApplication($fieldName, $filedValue, $realObj, $realValObj)
    {
        if (strtolower($fieldName) === 'updated') { # ignore updated
            return null;
        } else {
            return $this->normalize($filedValue);
        }
    }

    /**
     * Handles Application to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleElement($fieldName, $filedValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Application) { # handle Application
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } else {
            return $this->normalize($filedValue);
        }
    }

    /**
     * Handles Application to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleLayerset($fieldName, $filedValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Application) { # handle Application
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } else {
            return $this->normalize($filedValue);
        }
    }

    /**
     * Handles Application to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSourceInstance($fieldName, $filedValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Layerset) { # handle Layerset
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } elseif ($realValObj instanceof Source) { # handle Source
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } else {
            return $this->normalize($filedValue);
        }
    }
    
    

    /**
     * Handles Application to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSourceInstanceItem($fieldName, $filedValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof SourceInstance){
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } elseif ($realValObj instanceof SourceInstanceItem) { # handle 
            if (strtolower($fieldName) === 'parent') {
                return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
            } elseif (strtolower($fieldName) !== 'sublayer') {
                return $this->normalize($filedValue);
            }
        } elseif ($realValObj instanceof Source) { # handle Source
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } elseif ($realValObj instanceof SourceItem) { # handle Source
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } else {
            return $this->normalize($filedValue);
        }
    }

    /**
     * Handles Application to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleRegionProperties($fieldName, $filedValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Application) { # handle Application
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } else {
            return $this->normalize($filedValue);
        }
    }

    /**
     * Handles Source to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSource($fieldName, $filedValue, $realObj, $realValObj)
    {
        if (strtolower($fieldName) !== 'instance') { # ignore instance collection
            return $this->normalize($filedValue);
        }
        return null;
    }

    /**
     * Handles SourceItem to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSourceItem($fieldName, $filedValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Source) {
            return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
        } elseif ($realValObj instanceof SourceItem) {
            if (strtolower($fieldName) === 'parent') {
                return $this->createInstanceIdent($realValObj, array('id' => $filedValue->getId()));
            } elseif (strtolower($fieldName) !== 'sublayer') {
                return $this->normalize($filedValue);
            }
        }
        return null;
    }

    /**
     * Handles PersistentCollection to normalize
     * 
     * @param array $data array with normalized data
     * @param string $fieldName field name
     * @param mixed $filedValue field value
     * @param mixed $realObj object to normalize (real object without Doctrine Proxy)
     * @param mixed $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handlePersistentCollection($fieldName, $realObj, $filedValue)
    {
        if($realObj instanceof SourceItem){ # handle no collection for SourceItem
            
        } elseif($realObj instanceof SourceInstanceItem){ # handle no collection for SourceItem

        } elseif ($fieldName === 'keywords') { # handle no collection for keywords (Source, SourceItem)
            
//        } elseif ($fieldName === 'layersets') {
            
//        } elseif ($fieldName === 'regionProperties') {
            
//        } elseif ($fieldName === 'elements') {
            
        } else { # handle other
            $result = array();
            $collection = $filedValue->toArray();
            foreach ($collection as $collItem) {
                $result[] = $this->normalize($collItem);
            }
            return $result;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        $class = new \ReflectionClass(get_class($data));
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (is_object($data) && $this->findPropertyName($method)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Finds a property name from a "get" or "is" function.
     * 
     * @param string $method A method name
     * @param array $properties object properties.
     * @return string a property name or null
     */
    private function findPropertyName($method, $properties = array())
    {
        $methodName = $method->getName();
        if ($method->getNumberOfRequiredParameters() !== 0) {
            return null;
        } else if (strpos(strtolower($methodName), 'get') === 0) {
            return lcfirst(substr($methodName, 3));
        } else if (strpos(strtolower($methodName), 'is') === 0) {
            return lcfirst(substr($methodName, 2));
        } else {
            return null;
        }
    }

}
