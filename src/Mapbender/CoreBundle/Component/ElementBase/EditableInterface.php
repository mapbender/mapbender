<?php


namespace Mapbender\CoreBundle\Component\ElementBase;

/**
 * Methods that an Element must implement to support backend configuration forms, which happens when
 * 1) a new Element is added to an application
 * 2) an existing Element is edited
 */
interface EditableInterface extends AddableInterface
{
    /**
     * Should return the element configuration form type for backend configuration. Form type
     * must be indicated by fully qualified class name. Returning null or another falsy value
     * will prevent backend configuration.
     *
     * @return string|null
     */
    public static function getType();

    /**
     * Should return a twig-style 'BundleName:section:filename.html.twig' reference to the HTML template used
     * for rendering the backend configuration form.
     *
     * @return string
     */
    public static function getFormTemplate();
}
