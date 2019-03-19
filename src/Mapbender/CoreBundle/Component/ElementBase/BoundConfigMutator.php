<?php


namespace Mapbender\CoreBundle\Component\ElementBase;


/**
 * Interface for an element that dynamically rewrites application config, outside of its own
 * scope.
 */
interface BoundConfigMutator extends BoundEntityInterface
{
    /**
     * Receives the entire application configuration as an array, and should return the modified
     * version.
     *
     * @param array
     * @return array
     */
    public function updateAppConfig($configIn);
}
