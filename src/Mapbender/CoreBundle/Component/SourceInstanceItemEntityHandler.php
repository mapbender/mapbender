<?php
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
     * Update instance item parameters
     *
     * @param SourceInstance $instance
     * @param SourceItem     $wmslayersource
     */
    abstract public function update(SourceInstance $instance, SourceItem $wmslayersource);
}
