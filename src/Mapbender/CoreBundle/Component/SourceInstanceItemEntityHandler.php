<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Entity\SourceItem;

/**
 * @author Paul Schmidt
 *
 * @property SourceInstanceItem $entity
 */
abstract class SourceInstanceItemEntityHandler extends EntityHandler
{

    /**
     * Update instance item parameters
     *
     * @param SourceInstance $instance
     * @param SourceItem     $wmslayersource
     */
    abstract public function update(SourceInstance $instance, SourceItem $wmslayersource);
}
