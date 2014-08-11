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
use Mapbender\CoreBundle\Utils\EntityAnnotationParser;
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
        $realObj = $this->createRealObject($object);
        $data = $this->createInstanceIdent($realObj);
        $fields = EntityAnnotationParser::parseFieldsAnnotations(get_class($object), false);
        foreach ($fields as $fieldName => $filedProps) {
            if (!isset($filedProps['getter']) ||
                ($realObj instanceof SourceInstance && $fieldName === 'configuration')) {
                continue;
            }
            $reflectionMethod = new \ReflectionMethod(get_class($object), $filedProps['getter']);
            $fieldValue = $reflectionMethod->invoke($object);
            if ($fieldValue === null) {
                $data[$fieldName] = $fieldValue;
            } else if (is_integer($fieldValue) || is_float($fieldValue) || is_string($fieldValue) || is_bool($fieldValue)) {
                $data[$fieldName] = $fieldValue;
            } else if (is_array($fieldValue)) {
                $data[$fieldName] = $this->handleArray($fieldName, $fieldValue, $realObj);
            } else if (is_object($fieldValue)) {
                if ($fieldValue instanceof PersistentCollection) {
                    $data[$fieldName] = $this->handlePersistentCollection($fieldName, $fieldValue, $realObj);
                } else { # handle objects
                    $realValObj = $this->createRealObject($fieldValue);
                    if ($realObj instanceof SourceItem) {
                        $data[$fieldName] = $this->handleSourceItem($fieldName, $fieldValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof Source) {
                        $data[$fieldName] = $this->handleSource($fieldName, $fieldValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof Application) {
                        $data[$fieldName] = $this->handleApplication($fieldName, $fieldValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof Layerset) {
                        $data[$fieldName] = $this->handleLayerset($fieldName, $fieldValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof SourceInstance) {
                        $data[$fieldName] = $this->handleSourceInstance($fieldName, $fieldValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof SourceInstanceItem) {
                        $data[$fieldName] = $this->handleSourceInstanceItem($fieldName, $fieldValue, $realObj,
                            $realValObj);
                    } elseif ($realObj instanceof Element) {
                        $data[$fieldName] = $this->handleElement($fieldName, $fieldValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof RegionProperties) {
                        $data[$fieldName] = $this->handleRegionProperties($fieldName, $fieldValue, $realObj, $realValObj);
                    } elseif ($realObj instanceof \DateTime) {
                        return null;
                    } else {
                        $data[$fieldName] = $this->normalize($fieldValue);
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
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleApplication($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if (strtolower($fieldName) === 'updated') { # ignore updated
            return null;
        } else {
            return $this->normalize($fieldValue);
        }
    }

    /**
     * Handles Application to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleElement($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Application) { # handle Application
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        } else {
            return $this->normalize($fieldValue);
        }
    }

    /**
     * Handles Application to normalize
     *
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleLayerset($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Application) { # handle Application
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        } else {
            return $this->normalize($fieldValue);
        }
    }

    /**
     * Handles Application to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSourceInstance($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Layerset) { # handle Layerset
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        } elseif ($realValObj instanceof Source) { # handle Source
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        } else {
            return $this->normalize($fieldValue);
        }
    }

    /**
     * Handles Application to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSourceInstanceItem($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof SourceInstance) {
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        } elseif ($realValObj instanceof SourceInstanceItem) { # handle 
            if (strtolower($fieldName) === 'parent') {
                return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
            } elseif (strtolower($fieldName) !== 'sublayer') {
                return $this->normalize($fieldValue);
            }
        } elseif ($realValObj instanceof SourceItem) { # handle Source
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        }
        return null;
    }

    /**
     * Handles Application to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleRegionProperties($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Application) { # handle Application
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        } else {
            return $this->normalize($fieldValue); # 
        }
    }

    /**
     * Handles Source to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSource($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if (strtolower($fieldName) !== 'instance') { # ignore instance collection
            return $this->normalize($fieldValue);
        }
        return null;
    }

    /**
     * Handles SourceItem to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleSourceItem($fieldName, $fieldValue, $realObj, $realValObj)
    {
        if ($realValObj instanceof Source) {
            return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
        } elseif ($realValObj instanceof SourceItem) {
            if (strtolower($fieldName) === 'parent') {
                return $this->createInstanceIdent($realValObj, array('id' => $fieldValue->getId()));
            } elseif (strtolower($fieldName) !== 'sublayer') {
                return $this->normalize($fieldValue);
            }
        } else {
            return $this->normalize($fieldValue);
        }
        return null;
    }

    /**
     * Handles PersistentCollection to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handlePersistentCollection($fieldName, $fieldValue, $realObj)
    {
        if ($realObj instanceof SourceItem) { # handle no collection for SourceItem
        } elseif ($realObj instanceof SourceInstanceItem) { # handle no collection for SourceItem
        } else { # handle other
            $result = array();
            $collection = $fieldValue->toArray();
            foreach ($collection as $collItem) {
                $result[] = $this->normalize($collItem);
            }
            return $result;
        }
        return null;
    }

    /**
     * Handles PersistentCollection to normalize
     * 
     * @param string $fieldName field name
     * @param object $fieldValue field value
     * @param object $realObj object to normalize (real object without Doctrine Proxy)
     * @param object $realValObj object value to normalize (real object without Doctrine Proxy)
     */
    private function handleArray($fieldName, $fieldValue, $realObj)
    {
        $result = array();
        foreach ($fieldValue as $item) {
            if (is_array($item)) {
                $result[] = $this->handleArray($fieldName, $item, $realObj);
            } else if (is_object($item)) {
                $result[] = $this->normalize($item);
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return true;
    }
}
