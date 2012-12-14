<?php
namespace Mapbender\CoreBundle\Component;

/**
 * InstanceIn interface references the mapbender client's configuration.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
interface InstanceIn {
    
    /**
     * Creates the mapbender client's configuration.
     * @return array configuration parameters
     */
    public function getConfiguration();
}
