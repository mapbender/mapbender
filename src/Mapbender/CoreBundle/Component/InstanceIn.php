<?php
namespace Mapbender\CoreBundle\Component;

/**
 * InstanceIn interface.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
interface InstanceIn {
    /**
     * Creates and gets the mapbender configuration.
     */
    public function getConfiguration();
}
