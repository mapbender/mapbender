<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ExchangeNormalizer class normalizes objects to array.
 *
 * @author Paul Schmidt
 */
abstract class ExchangeSerializer
{

    const KEY_CLASS = '__class__';
    const KEY_SLUG = 'slug';
    const KEY_IDENTIFIER = 'identifier';
    const KEY_GETTER = 'getter';
    const KEY_SETTER = 'setter';
    const KEY_COLUMN = 'Column';
    const KEY_UNIQUE = 'unique';
    const KEY_MAP = 'map';
    const KEY_PRIMARY = 'primary';
    const KEY_CONFIGURATION = 'configuration';

    protected $container;
    
    /**
     * 
     * @param ContainerInterface $container container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

        
    public function getParentClasses($class, $parents = array())
    {
        $parent = get_parent_class($class);
        if ($parent) {
            $plist[$parent] = $parent;
            $plist = $this->getParentClasses($parent, $plist);
        }
    }

    public function createRealObject($object)
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

    public function createInstanceIdent($object, $params = array())
    {
        return array_merge(
            array(
            self::KEY_CLASS => array(
                get_class($object),
                array())
            ), $params
        );
    }
    
    public function getClassName($data)
    {
        $class = $this->getClassDifinition($data);
        if(!$class){
            return null;
        } else {
            return $class[0];
        }
    }
    public function getClassDifinition($data)
    {
        if(!$data || !is_array($data)){
            return null;
        } elseif(key_exists(self::KEY_CLASS, $data)){
            return $data[self::KEY_CLASS];
        } else {
            return null;
        }
    }
    public function getClassConstructParams($data)
    {
        $class = $this->getClassDifinition($data);
        if(!$class){
            return array();
        } else {
            return $class[1];
        }
    }

}
