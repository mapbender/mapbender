<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

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
    abstract public function create();
    
    /**
     * Remove a source from a database
     */
    abstract public function remove();


}
