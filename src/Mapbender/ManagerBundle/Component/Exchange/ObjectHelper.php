<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


class ObjectHelper extends AbstractObjectHelper
{
    /** @var static[] */
    protected static $instances = array();

    /**
     * @param string|object $objectOrClassName
     * @return AbstractObjectHelper|null
     * @throws \ReflectionException
     */
    public static function getInstance($objectOrClassName)
    {
        $className = is_object($objectOrClassName) ? get_class($objectOrClassName) : $objectOrClassName;
        if (!array_key_exists($className, static::$instances)) {
            static::$instances[$className] = new static($className);
        }
        return static::$instances[$className] ?: null;
    }
}
