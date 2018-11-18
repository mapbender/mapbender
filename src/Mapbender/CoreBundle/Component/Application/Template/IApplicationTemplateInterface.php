<?php

namespace Mapbender\CoreBundle\Component\Application\Template;

/**
 * Workaround for PHP<7 not allowing abstract static functions to be declared outside of interfaces.
 */
interface IApplicationTemplateInterface
{
    /**
     * Should return the displayable title of the template. Listed in backend for
     * 1) template choice when creating new Application
     * 2) Application settings display
     *
     * @return string
     */
    public static function getTitle();


    /**
     * Should return the list of regions in the template that can be popuplated with Element output.
     *
     * @return string[]
     */
    public static function getRegions();
}
