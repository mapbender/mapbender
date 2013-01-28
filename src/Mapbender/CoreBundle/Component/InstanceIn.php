<?php
namespace Mapbender\CoreBundle\Component;

/**
 * InstanceIn interface references the mapbender client's configuration.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
interface InstanceIn {
    
    /**
     * Creates the mapbender configuration.
     * @return array configuration parameters
     */
    public function getConfiguration();
    
    /**
     * Creates the mapbender layer tree configuration.
     * @return array configuration parameters
     */
    public function getLayertreeConfiguration();
}
