<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Description of SourceInstanceEntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceInstanceEntityHandler extends EntityHandler
{
    /**
     * @param array $configuration
     * @return SourceInstance
     * @internal param SourceInstance $instance
     */
    abstract public function configure(array $configuration = array());

    /**
     * Creates a SourceInstance
     */
    abstract public function create($persist = true);
    
    /**
     * Update instance parameters
     */
    abstract public function update();
    
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
    
    /**
     * Returns an array with sensitive vendor specific parameters
     */
    abstract public function getSensitiveVendorSpecific();
}
