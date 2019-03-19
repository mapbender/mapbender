<?php


namespace Mapbender\CoreBundle\Component\ElementBase;


/**
 * Methods that an Element must implement to be visible in the backend
 * * Appearance in Application "Layouts" list
 * * Appearance in element selection list shown when hitting "new element" in an application
 */
interface AddableInterface extends MinimalInterface
{
    /**
     * Should return the long-form element description, which is displayed just under the title in the list of available
     * Elements, when adding to an application in the backend.
     *
     * Subject to translation.
     *
     * @return string
     */
    public static function getClassDescription();
}
