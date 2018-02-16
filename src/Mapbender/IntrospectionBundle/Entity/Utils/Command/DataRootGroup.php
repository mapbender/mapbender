<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


/**
 * Stealthy group container that has no own id or name, but can convert multiple sub-items to a flat list / table
 * nicely.
 *
 * @package Mapbender\IntrospectionBundle\Entity\Utils\Command
 */
class DataRootGroup extends DataGroup
{
    public function __construct()
    {
        parent::__construct(null, null);
    }

    /**
     * @inheritdoc
     */
    public function toArray(DataItemFormatting $format, $dataTypeLabels = array())
    {
        $list = array();
        foreach ($this->items as $item) {
            $list[] = $item->toArray($format, $dataTypeLabels);
        }
        return $list;
    }

    /**
     * @return string[][]
     */
    public function toGrid()
    {
        // we do not have any data to add, so the column width of the grid doesn't change here
        // => perform trivial bulk list merging
        $subGrids = array();
        foreach ($this->items as $item) {
            $subGrids[] = $item->toGrid();
        }
        return call_user_func_array('array_merge', $subGrids);
    }
}
