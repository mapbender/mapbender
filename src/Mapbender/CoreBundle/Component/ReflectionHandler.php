<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Utils\EntityUtil;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Description of ReflectionUtil
 *
 * @author Paul Schmidt
 */
class ReflectionHandler
{

    protected $reflectionClass;
    protected $class;
    protected $propertyFilter;
    protected $methodFilter;
    protected $properties;
    protected $methods;
    protected $mapper;

    /**
     * Creates an instance of ReflectionClass
     * @param object $object an object
     * @param int | null $propertyFilter Filter the results to include only property certain attributes. Defaults 
     * (for propertyFilter is null) to: "ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED"
     * @param int | null $methodFilter Filter the results to include only methods with certain attributes. Defaults 
     * (for methodFilter is null) to: "ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED"
     */
    public function __construct($object, $propertyFilter = null, $methodFilter = null)
    {
        $this->class = EntityUtil::getRealClass($object);
        $this->reflectionClass = new ReflectionClass($this->class);
        $this->setPropertyFilter($propertyFilter);
        $this->setMethodFilter($methodFilter);
        $this->createMapper();
    }
    
    public function getClass()
    {
        return $this->class;
    }

    public function getMapper()
    {
        return $this->mapper;
    }

    public function createMapper()
    {
        $this->mapper = array();
        foreach ($this->properties as $property) {
            $temp = strtolower(str_replace('_', '', $property->name));
            $this->mapper[$property->name] = array();
            foreach ($this->methods as $method) {
                if (strtolower($method->name) === EntityUtil::GET . $temp) {
                    $this->mapper[$property->name][EntityUtil::GETTER] = $method->name;
                } elseif (strtolower($method->name) === EntityUtil::SET . $temp) {
                    $this->mapper[$property->name][EntityUtil::TOSET] = $method->name;
                    $this->mapper[$property->name][EntityUtil::SETTER] = $method->name;
                } elseif (strtolower($method->name) === EntityUtil::IS . $temp) {
                    $this->mapper[$property->name][EntityUtil::TOSET] = $method->name;
                    $this->mapper[$property->name][EntityUtil::IS_METHOD] = $method->name;
                } elseif (strtolower($method->name) === EntityUtil::HAS . $temp) {
                    $this->mapper[$property->name][EntityUtil::TOSET] = $method->name;
                    $this->mapper[$property->name][EntityUtil::HAS_METHOD] = $method->name;
                }
            }
        }
    }

    public function getMethodFilter()
    {
        return $this->methodFilter;
    }

    public function setMethodFilter($methodFilter = NULL)
    {
        $this->methodFilter = $methodFilter === null ?
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED : $methodFilter;
        $this->methods = $this->reflectionClass->getMethods($this->methodFilter);
    }

    public function getPropertyFilter()
    {
        return $this->propertyFilter;
    }

    public function setPropertyFilter($propertyFilter = NULL)
    {
        $this->propertyFilter = $propertyFilter === null ?
            ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED : $propertyFilter;
        $this->properties = $this->reflectionClass->getProperties($this->propertyFilter);
    }

}
