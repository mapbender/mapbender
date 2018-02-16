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
    public function toArray($dataTypeLabels = array())
    {
        $list = array();
        foreach ($this->items as $item) {
            $list[] = $item->toArray($dataTypeLabels);
        }
        return $list;
    }

    /**
     * @return string[][]
     */
    public function toGrid()
    {
        $subGrids = array();
        foreach ($this->items as $item) {
            $subGrids[] = $item->toGrid();
        }
        return call_user_func_array('array_merge', $subGrids);
    }
}
