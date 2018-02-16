<?php

namespace Mapbender\IntrospectionBundle\Utils;

class BundleUtil
{
    /**
     * Get the bundle's base namespace (e.g. "Mapbender\CoreBundle") from any class name inside it.
     *
     * @param string $className
     * @return string
     */
    public static function extractBundleNamespace($className)
    {
        $classNameParts = explode('\\', $className);
        return implode('\\', array_slice($classNameParts, 0, 2));
    }

    /**
     * Calculate the bundle's expected name (e.g. "MapbenderCoreBundle") from any class name inside that bundle's
     * namespace. "Expected" means we fuse the first two namespace parts.
     *
     * @param string $className
     * @return string
     */
    public static function extractBundleNameFromClassName($className)
    {
        $classNameParts = explode('\\', $className);
        return implode('', array_slice($classNameParts, 0, 2));
    }

    /**
     * Return the bundle name from the given twig-style template path (":" separators)
     *
     * @param string $templatePath twig-style (":" separators")
     * @return string
     */
    public static function extractBundleNameFromTemplatePath($templatePath)
    {
        if (strpos($templatePath, '/') === 0) {
            throw new \UnexpectedValueException("Path " . var_export($templatePath) . " is absolute");
        }
        return implode('', array_slice(explode(':', $templatePath), 0, 1));
    }

    /**
     * Return the bundle name from the given resource-style path (":" separators, optional "@" prefix)
     *
     * @param string $resourcePath
     * @return string
     */
    public static function extractBundleNameFromResourcePath($resourcePath)
    {
        return static::extractBundleNameFromTemplatePath(ltrim($resourcePath, '@'));
    }
}
