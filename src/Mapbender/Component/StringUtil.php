<?php


namespace Mapbender\Component;


class StringUtil
{
    /**
     * 'DoctrineMigrationsHelper' => 'doctrine_migrations_helper'
     * 'WithCAPSInTheMiddle' => 'with_capsin_the_middle' :\
     * Not digit-safe.
     * Not space-safe.
     * Should only be used for class names.
     *
     * @param string $x
     * @return string
     */
    public static function camelToSnakeCase($x)
    {
        // insert underscores before upper case letter following lower-case
        $withUnderscores = preg_replace('/([^A-Z])([A-Z])/', '\\1_\\2', $x);
        // lower-case the whole thing
        return strtolower($withUnderscores);
    }

    /**
     * 'doctrine_migrations_helper' => 'DoctrineMigrationsHelper'
     * 'with_capsin_the_middle' => 'WithCapsinTheMiddle' :\
     *
     * @param string $x
     * @param bool $firstUpper to capitalize first letter of output (default true)
     * @return string
     */
    public static function snakeToCamelCase($x, $firstUpper = true)
    {
        $fragments = explode('_', $x);
        if ($fragments && !$firstUpper) {
            $parts = array_merge(array($fragments[0]), array_map('ucfirst', array_slice($fragments, 1)));
        } else {
            $parts = array_map('ucfirst', $fragments);
        }
        return implode('', $parts);
    }
}
