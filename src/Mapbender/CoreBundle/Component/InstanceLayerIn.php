<?php

namespace Mapbender\CoreBundle\Component;

/**
 * InstanceLayer
 * 
 * @author Paul Schmidt
 */
interface InstanceLayerIn
{

    /**
     * Creates the mapbender configuration.
     * @return array configuration parameters
     */
    public function getConfiguration();
    
}

?>
