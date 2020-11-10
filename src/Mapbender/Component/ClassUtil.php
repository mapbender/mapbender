<?php


namespace Mapbender\Component;


class ClassUtil
{

    /**
     * @param object|string $instanceOrName
     * @return string[]
     * @throws \InvalidArgumentException if $instanceOrName is falsy
     */
    public static function getParents($instanceOrName)
    {
        $classes = array();
        $currentName = static::toName($instanceOrName);
        for ($i = 0; $currentName; ++$i) {
            if ($i) {
                $classes[] = $currentName;
            }
            $currentName  = get_parent_class($currentName);
        }
        return $classes;
    }

    /**
     * @param object|string $instanceOrName
     * @param object|string $stopClass
     * @param bool $includeStop
     * @return string[]
     * @throws \InvalidArgumentException if $instanceOrName or $stopClass are falsy
     */
    public static function getParentsUntil($instanceOrName, $stopClass, $includeStop = false)
    {
        $namesOut = array();
        $stopClass = static::toName($stopClass);
        foreach (static::getParents($instanceOrName) as $parentName) {
            if ($includeStop || $parentName != $stopClass) {
                $namesOut[] = $parentName;
            }
            if ($parentName == $stopClass) {
                break;
            }
        }
        return $namesOut;
    }

    /**
     * @param object|string $instanceOrName
     * @param object|string|null $stopClass
     * @param bool $includeStop
     * @return string
     * @throws \InvalidArgumentException if $instanceOrName is falsy
     */
    public static function getBaseClass($instanceOrName, $stopClass = null, $includeStop = false)
    {
        if ($stopClass) {
            $parents = static::getParentsUntil($instanceOrName, $stopClass, $includeStop);
        } else {
            $parents = static::getParents($instanceOrName);
        }
        if ($parents) {
            // return last element
            return implode('', array_slice($parents, -1));
        } else {
            // no (matched) parents, return name of passed class itself
            return static::toName($instanceOrName);
        }
    }

    /**
     * @param object|string $instanceOrName
     * @return string
     * @throws \InvalidArgumentException if $instanceOrName is falsy
     */
    public static function toName($instanceOrName)
    {
        // This might seem a bit redundant in light of PHP5.5's ::class, but consider:
        // php > echo null::class . "\n";
        // null
        // php > echo get_class(null) . "\n"
        // Mapbender\Component\ClassUtil   (PHP interprets null as a request for current class's name)
        if (!$instanceOrName) {
            throw new \InvalidArgumentException("Unsupported value " . var_export($instanceOrName, true));
        }
        return is_object($instanceOrName) ? get_class($instanceOrName) : $instanceOrName;
    }
}
