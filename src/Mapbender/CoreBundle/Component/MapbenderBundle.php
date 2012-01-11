<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MapbenderBundle extends Bundle {
    /**
     * Return list of element classes provided by this bundle.
     * Each entry in the array is a fully qualified class name.
     *
     * @return array() Array of element class names
     */
    public function getElements() {
        return array();
    }

    /**
     * Return list of layer classes provided by this bundle.
     * Each entry in the array is a fully qualified class name.
     *
     * @return array() Array of layer class names
     */
    public function getLayers() {
        return array();
    }

    /**
     * Return list of template classes provided by this bundle.
     * Each entry in the array is a fully qualified class name.
     *
     * @return array() Array of template class names
     */
    public function getTemplates() {
        return array();
    }
}

