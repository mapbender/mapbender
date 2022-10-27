<?php
namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Legacy mapbender bundle with methods to list Element and Template classes
 *
 * @author Christian Wygoda
 *
 * @deprecated
 *
 * Declare services with `mapbender.element` tag to add custom elements
 *    See https://github.com/mapbender/mapbender/pull/1367
 * Declare services with `mapbender.application_template` tag to add custom application templates
 *    or displace existing Mapbender templates. See https://github.com/mapbender/mapbender/pull/1424
 */
class MapbenderBundle extends Bundle
{
    /**
     * Return list of element classes provided by this bundle.
     * Each entry in the array is a fully qualified class name.
     *
     * @return string[]
     */
    public function getElements()
    {
        return array();
    }

    /**
     * Return list of template classes provided by this bundle.
     * Each entry in the array is a fully qualified class name.
     *
     * @return string[]
     */
    public function getTemplates()
    {
        return array();
    }

}

