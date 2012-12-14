<?php
namespace Mapbender\CoreBundle\Component;

/**
 * HasInstanceIn interface references SourceInstance.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
interface HasInstanceIn {

    /**
     * Creates a SourceInstance.
     * @return SourceInstance 
     */
    public function createInstance();
}
