<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\SourceItem;

/**
 * Description of SourceItemEntityHandler
 *
 * @author Paul Schmidt
 */
abstract class SourceItemEntityHandler extends EntityHandler
{

    /**
     * Updates a SourceItem from another SourceItem
     * @param SourceItem $sourceItem a SourceItemobject
     */
    abstract public function update(SourceItem $sourceItem);
}
