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
     * Generates an item configuration
     */
    abstract public function generateConfiguration();

    /**
     * Returns an item configuration
     */
    abstract public function getConfiguration();

    /**
     * Update instance item parameters
     *
     * @param SourceInstance $instance
     * @param SourceItem     $wmslayersource
     * @return
     */
    abstract public function update(SourceInstance $instance, SourceItem $wmslayersource);
}
