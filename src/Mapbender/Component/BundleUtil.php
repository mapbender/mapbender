<?php

namespace Mapbender\Component;


class BundleUtil
{
    /**
     * Get the bundle's base namespace (e.g. "Mapbender\CoreBundle") from any class name inside it.
     *
     * @param string $className
     * @return string
     * @throws \RuntimeException if matching fails
     */
    public static function extractBundleNamespace($className)
    {
        $parts = array();
        $matched = false;
        foreach (explode('\\', $className) as $part) {
            $parts[] = $part;
            if (preg_match('#Bundle$#', $part)) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            throw new \RuntimeException("No namespace fragment of class name " . var_export($className, true) . " matches 'Bundle$'");
        }
        return implode('\\', $parts);
    }

    /**
     * Calculate the bundle's expected name (e.g. "MapbenderCoreBundle") from any class name inside that bundle's
     * namespace. "Expected" means we fuse the first two namespace parts.
     *
     * @param string $className
     * @return string
     * @throws \RuntimeException if matching fails
     */
    public static function extractBundleNameFromClassName($className)
    {
        $namespaceParts = explode('\\', static::extractBundleNamespace($className));
        // convention alert: fuse last two parts
        return implode('', array_slice($namespaceParts, -2));
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

    /**
     * Returns the remaining class path inside the auto-detected bundle namespace
     * 'Mapbender\ExampleBundle\Extensive\Sub\Namespace\Class' => 'Extensive\Sub\Namespace\Class'
     *
     * @param $className
     * @return bool|string
     * @throws \RuntimeException if bundle matching fails
     */
    public static function getNameInsideBundleNamespace($className)
    {
        return substr($className, strlen(static::extractBundleNamespace($className)) + 1);
    }
}
