<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;

/**
 * Description of EntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceEntityHandler extends EntityHandler
{
    
    /**
     * Creates a SourceInstance
     * @param boolean $persist a flag to save the entity
     */
    abstract public function create($persist = true);
        
    /**
     * Creates a SourceInstance
     * @param Layerset $layerset layerset
     * @param boolean $persist a flag to save the entity
     */
    abstract public function createInstance(Layerset $layerset = NULL, $persist = true);
    
    /**
     * Remove a source from a database
     */
    abstract public function remove();



    /**
     * Saves an entity
     */
    abstract public function save();
    
    /**
     * Update a source from a new source
     * @param Source $source a Source object
     */
    abstract public function update(Source $source);

    /**
     * Returns a source from a database
     */
    abstract public function getInstances();


}
