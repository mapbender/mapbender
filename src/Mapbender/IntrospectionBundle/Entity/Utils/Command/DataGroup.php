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
     * @param DataItemFormatting $format
     * @param string[] $itemTypeLabels
     * @return array
     */
    public function toArray(DataItemFormatting $format, $itemTypeLabels = array())
    {
        $baseValues = parent::toArray($format);
        $children = $this->childrenToArray($format, array_slice($itemTypeLabels, 1));
        if ($children) {
            if (!empty($itemTypeLabels[0])) {
                $currentItemTypeLabel = $itemTypeLabels[0];
            } else {
                $currentItemTypeLabel = 'items';
            }
            $baseValues[$currentItemTypeLabel] = $children;
        }
        return $baseValues;
    }

    /**
     * @param DataItemFormatting $format
     * @param string[] $subItemTypeLabels
     * @return array
     */
    public function childrenToArray(DataItemFormatting $format, $subItemTypeLabels = array())
    {
        if ($this->items) {
            $subValues = array();
            foreach ($this->items as $subItem) {
                if ($format->hoistIds) {
                    $subValues[$subItem->id] = $subItem->toArray($format, $subItemTypeLabels);
                } else {
                    $subValues[] = $subItem->toArray($format, $subItemTypeLabels);
                }
            }
            return $subValues;
        } else {
            return array();
        }
    }
}
