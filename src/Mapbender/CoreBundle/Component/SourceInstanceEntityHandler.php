<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Component\Signer;

/**
 * Description of EntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceInstanceEntityHandler extends EntityHandler
{
        
    /**
     * Creates a SourceInstance
     */
    abstract public function create($persist = true);
    
    /**
     * Update instance parameters
     */
    abstract public function update();
    
    /**
     * Remove a source from a database
     */
    abstract public function remove();
    
    /**
     * Returns the instance configuration with signed urls.
     */
    abstract public function getConfiguration(Signer $signer);
    
    /**
     * Generates an instance configuration
     */
    abstract public function generateConfiguration();
    
    /**
     * Merges a fiving dimension with an existing.
     * @param Dimension $dimension a diemsion
     * @param boolean $persist Description
     */
    abstract public function mergeDimension($dimension, $persist = false);


}
