<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceItem;

/**
 * @author Paul Schmidt
 *
 * @property SourceItem $entity
 */
abstract class SourceItemEntityHandler extends EntityHandler
{

    /**
     * Updates a SourceItem from another SourceItem
     * @param SourceItem $sourceItem a SourceItemobject
     */
    abstract public function update(SourceItem $sourceItem);
}
