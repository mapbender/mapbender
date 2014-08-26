<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Component\SourceItem;
/**
 * Description of EntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceInstanceItemEntityHandler extends EntityHandler
{
        
    /**
     * Creates a SourceInstanceItem
     */
    abstract public function create(SourceInstance $instance, SourceItem $item, $num = 0);
    
    /**
     * Remove a SourceInstanceItem
     */
    abstract public function remove();


}
