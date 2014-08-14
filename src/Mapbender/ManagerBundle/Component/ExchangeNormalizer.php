<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\PersistentCollection;
use Mapbender\CoreBundle\Component\Element as ElementComponent;
use Mapbender\CoreBundle\Component\SourceItem;
use Mapbender\CoreBundle\Component\SourceInstanceItem;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\EntityAnnotationParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * ExchangeNormalizer class normalizes objects to array.
 *
 * @author Paul Schmidt
 */
class ExchangeNormalizer extends ExchangeSerializer implements NormalizerInterface
{
    /**
     * 
     * @param ContainerInterface $container container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }
    
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $realObj = $this->createRealObject($object);
        $data = $this->createInstanceIdent($realObj);
        $fields = EntityAnnotationParser::parseFieldsAnnotations(get_class($object), false);
        foreach ($fields as $fieldName => $fieldProps) {
            if (!isset($fieldProps[self::KEY_GETTER]) ||
                ($realObj instanceof SourceInstance && $fieldName === self::KEY_CONFIGURATION)) {
                continue;
            }
            $reflectionMethod = new \ReflectionMethod(get_class($object), $fieldProps[self::KEY_GETTER]);
            $fieldValue = $reflectionMethod->invoke($object);
            if ($fieldValue === null) {
                $data[$fieldName] = $fieldValue;
            } else if (is_integer($fieldValue) || is_float($fieldValue) || is_string($fieldValue) || is_bool($fieldValue)) {
                $data[$fieldName] = $fieldValue;
            } else if (is_array($fieldValue)) {
                if($realObj instanceof Element && $fieldName === self::KEY_CONFIGURATION){
                    $data[$fieldName] = $this->handleElementConfiguration($fieldName, $fieldValue, $object, $realObj);
                } else {
                    $data[$fieldName] = $this->handleArray($fieldName, $fieldValue, $realObj);
                }
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
            } elseif (strtolower($fieldName) === 'sublayer') {
                return $this->normalize($fieldValue);
            } else {
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
            } else {
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
        if ($realObj instanceof SourceItem || $realObj instanceof SourceInstanceItem) {
            $result = array();
            $collection = $fieldValue->toArray();
            foreach ($collection as $collItem) {
                $result[] = $this->createInstanceIdent($collItem, array('id' => $collItem->getId()));
            }
            return $result;
        } elseif ($realObj instanceof Layerset) {
            $result = array();
            $collection = $fieldValue->toArray();
            foreach ($collection as $collItem) {
                $inst = $this->normalize($collItem);
                $result[] = $inst;#$this->createInstanceIdent($collItem, array('id' => $collItem->getId()));
            }
            return $result;
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
        if (ArrayUtil::isAssoc($fieldValue)) {
            foreach ($fieldValue as $key => $item) {
                if (is_array($item)) {
                    $result[$key] = $this->handleArray($fieldName, $item, $realObj);
                } else if (is_object($item)) {
                    $result[$key] = $this->normalize($item);
                } else {
                    $result[$key] = $item;
                }
            }
        } else {
            foreach ($fieldValue as $item) {
                if (is_array($item)) {
                    $result[] = $this->handleArray($fieldName, $item, $realObj);
                } else if (is_object($item)) {
                    $result[] = $this->normalize($item);
                } else {
                    $result[] = $item;
                }
            }
        }
        return $result;
    }
    
    private function handleElementConfiguration($fieldName, $fieldValue, Element $element, $realObj)
    {
        $form = ElementComponent::getElementForm($this->container, $element->getApplication(), $element);
        $configuration = $element->getConfiguration();
        foreach ($form['form'][self::KEY_CONFIGURATION]->all() as $fieldName => $fieldValue) {
            $norm = $fieldValue->getNormData();
            if ($norm instanceof Element || $norm instanceof Layerset || $norm instanceof Application ||
                $norm instanceof RegionProperties || $norm instanceof Source || $norm instanceof SourceItem ||
                $norm instanceof SourceInstance || $norm instanceof SourceInstanceItem) {
                $configuration[$fieldName] = $this->createInstanceIdent($norm, array('id' => $norm->getId()));
            }
        }
        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return true;
    }
}
