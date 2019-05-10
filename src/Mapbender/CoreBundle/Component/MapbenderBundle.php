<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\ManagerBundle\Component\ManagerBundle;

/**
 * The base bundle class for all Mapbender3 bundles.
 *
 * Mapbender3 bundles are special in a way as they expose lists of their
 * elements, layers and templates for the central Mapbender3 service, which
 * aggregates these for use in the manager backend.
 *
 * @author Christian Wygoda
 */
class MapbenderBundle extends ManagerBundle
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

    /**
     * Source factories provide information about source importers/parsers/transformers
     *
     * @return array[]
     */
    public function getRepositoryManagers()
    {
        return array();
    }

}

