<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceItem;

/**
 * Description of SourceInstanceItemEntityHandler
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
     * Generates an item configuration
     */
    abstract public function generateConfiguration();

    /**
     * Returns an item configuration
     */
    abstract public function getConfiguration();

    /**
     * Update instance item parameters
     */
    abstract public function update(SourceInstance $instance, SourceItem $wmslayersource);
}
