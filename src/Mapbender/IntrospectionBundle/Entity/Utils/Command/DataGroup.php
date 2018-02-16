<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;


/**
 * Container for 1:n relationally grouped data with simple display styling.
 *
 * @package Mapbender\IntrospectionBundle\Entity\Utils\Command
 */
class DataGroup extends DataItem
{
    /** @var DataItem[] */
    protected $items;

    public function __construct($groupId, $groupName, $modifiers = array(), $wrapStyle = null)
    {
        parent::__construct($groupId, $groupName, $modifiers, $wrapStyle);
        $this->items = array();
    }

    /**
     * Append to this group's sub-items.
     *
     * @param DataItem $item
     */
    public function addItem(DataItem $item)
    {
        $this->items[] = $item;
    }

    /**
     * @return string[][]
     */
    public function toGrid()
    {
        $baseCells = array($this->toDisplayable());
        $rowsOut = array();

        foreach ($this->items as $item) {
            foreach ($item->toGrid() as $subRow) {
                $rowsOut[] = array_merge($baseCells, $subRow);
                // magic trick: omit group description after first row
                $baseCells = array("");
            }
        }
        return $rowsOut;
    }

    /**
     * Recursively dump this item and all its children into an array structure, where each node only has
     * 'id', 'name' and 'items'.
     * If you provide $itemTypeLabels, the 'items' subkey will be renamed, top down, until all provided
     * labels have been used. Then it's back to 'items'.
     *
     * @param string[] $itemTypeLabels
     * @return array
     */
    public function toArray($itemTypeLabels = array())
    {
        $baseValues = parent::toArray();
        if (!empty($itemTypeLabels[0])) {
            $currentItemTypeLabel = $itemTypeLabels[0];
            $subTypeLabels = array_slice($itemTypeLabels, 1);
        } else {
            $currentItemTypeLabel = 'item';
            $subTypeLabels = array();
        }
        if ($this->items) {
            $subValues = array();
            foreach ($this->items as $subItem) {
                $subValues[] = $subItem->toArray($subTypeLabels);
            }
            return array_replace($baseValues, array(
                $currentItemTypeLabel => $subValues,
            ));
        } else {
            return $baseValues;
        }
    }
}
