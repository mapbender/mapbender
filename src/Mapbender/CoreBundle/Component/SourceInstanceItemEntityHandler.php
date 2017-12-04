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
     * Creates a SourceInstanceItem
     *
     * @param SourceInstance $instance
     * @param SourceItem     $item
     * @param int            $num
     * @return
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
     *
     * @param SourceInstance $instance
     * @param SourceItem     $wmslayersource
     * @return
     */
    abstract public function update(SourceInstance $instance, SourceItem $wmslayersource);
}
