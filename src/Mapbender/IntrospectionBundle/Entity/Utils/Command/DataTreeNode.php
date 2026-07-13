<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


/**
 * Container for 1:n relationally grouped data with simple display styling.
 *
 * @package Mapbender\IntrospectionBundle\Entity\Utils\Command
 */
class DataTreeNode extends DataItem
{
    /** @var DataItem[] */
    protected $items;

    public function __construct($nodeId, $nodeName = null, $nodeModifiers = [], $wrapStyle = null)
    {
        parent::__construct($nodeId, $nodeName, $nodeModifiers, $wrapStyle);
        $this->items = [];
    }

    /**
     * @return DataItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Append to this group's sub-items.
     *
     * @param DataItem $item
     */
    public function addItem(DataItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return string[][]
     */
    public function childrenToGrid(): mixed
    {
        $subGrids = [[]]; // array_merge requires at least one argument
        foreach ($this->items as $item) {
            $subGrids[] = $item->toGrid();
        }
        return call_user_func_array('array_merge', $subGrids);
    }

    /**
     * @return string[][]
     */
    public function toGrid()
    {
        if ($this->name) {
            $rowsOut = [];
            $baseCells = [$this->toDisplayable()];
            foreach ($this->childrenToGrid() as $subRow) {
                $rowsOut[] = array_merge($baseCells, $subRow);
                // magic trick: omit group description after first row
                $baseCells = [""];
            }
            return $rowsOut;
        } else {
            return $this->childrenToGrid();
        }
    }

    /**
     * Recursively dump this item and all its children into an array structure.
     *
     * @param DataItemFormatting $format
     * @return array
     */
    public function toArray(DataItemFormatting $format): array
    {
        $localValues = array_filter(parent::toArray($format));
        $childValues = $this->childrenToArray($format);
        return array_merge($localValues, $childValues);
    }

    /**
     * @param DataItemFormatting $format
     * @return array
     */
    public function childrenToArray(DataItemFormatting $format): array
    {
        if ($this->items) {
            $subValues = [];
            foreach ($this->items as $subItem) {
                if ($format->hoistIds || $subItem instanceof DataItemList) {
                    $subValues[$subItem->getId()] = $subItem->toArray($format);
                } else {
                    $subValues[] = $subItem->toArray($format);
                }
            }
            return $subValues;
        } else {
            return [];
        }
    }
}
