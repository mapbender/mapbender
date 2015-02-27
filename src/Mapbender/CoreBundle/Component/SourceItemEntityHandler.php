<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\SourceItem;

/**
 * Description of EntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceItemEntityHandler extends EntityHandler
{
        
    /**
     * Creates a Sourcetem
     */
    abstract public function create();
    
    /**
     * Removes a SourceItem
     */
    abstract public function remove();
    
    /**
     * Updates a SourceItem from another SourceItem
     * @param SourceItem $sourceItem a SourceItemobject
     */
    abstract function update(SourceItem $sourceItem);

}
