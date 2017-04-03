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
     * Creates a Sourcetem
     */
    abstract public function create();

    /**
     * Updates a SourceItem from another SourceItem
     * @param SourceItem $sourceItem a SourceItemobject
     */
    abstract public function update(SourceItem $sourceItem);
}
