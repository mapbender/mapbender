<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


/**
 * Flat list container that has no local data (name, flags)
 *
 * @package Mapbender\IntrospectionBundle\Entity\Utils\Command
 */
class DataItemList extends DataTreeNode
{
    /**
     * @inheritdoc
     */
    public function toArray(DataItemFormatting $format)
    {
        return $this->childrenToArray($format);
    }

    /**
     * @return string[][]
     */
    public function toGrid()
    {
        // we do not have any data to add, so the column width of the grid doesn't change here
        // => perform trivial bulk list merging
        return $this->childrenToGrid();
    }
}
