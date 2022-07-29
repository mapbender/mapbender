<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\ManagerBundle\Component\ManagerBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The base bundle class for all Mapbender3 bundles.
 *
 * Mapbender3 bundles are special in a way as they expose lists of their
 * elements, layers and templates for the central Mapbender3 service, which
 * aggregates these for use in the manager backend.
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

