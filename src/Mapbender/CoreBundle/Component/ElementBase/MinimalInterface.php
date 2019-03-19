<?php


namespace Mapbender\CoreBundle\Component\ElementBase;

/**
 * The absolute bare minimum interface all Elements must fullfill.
 */
interface MinimalInterface
{
    /**
     * Returns the default value for the Element Entity's "configuration" array.
     *
     * This is used for two purposes:
     * 1) Backend form defaults for newly created elements
     * 2) Extending / amending incomplete configuration values from YAML applications or older element configs
     *    (new code but old db entries, import of old export etc)
     *
     * You must at least specify values for all backend form fields
     *
     * @return array
     */
    public static function getDefaultConfiguration();

    /**
     * Should return string title of Element for
     * 1) backend: selecting a new element when adding to an application
     * 2) backend: default value for "title" field in Element Entity
     * 3) frontend: default title for dialogs
     * 4) frontend: default value for certain hover tooltips
     *
     * Return value is subject to translation
     * @return string
     */
    public static function getClassTitle();
}
