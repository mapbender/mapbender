<?php

namespace Mapbender\CoreBundle\Utils;

use Doctrine\Persistence\Proxy;

/**
 * Utility class to replace the removed Doctrine\Common\Util\ClassUtils
 * (removed in doctrine/common 4 / Doctrine ORM 3).
 */
class DoctrineClassUtil
{
    /**
     * Returns the real class name of an object or class name string,
     * unwrapping Doctrine proxy classes if necessary.
     *
     * Replaces ClassUtils::getClass($object) and ClassUtils::getRealClass($className).
     *
     * @param object|string $objectOrClass
     * @return string
     */
    public static function getRealClass(object|string $objectOrClass): string
    {
        $className = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;
        if (str_contains($className, Proxy::MARKER)) {
            return get_parent_class($className) ?: $className;
        }
        return $className;
    }
}
