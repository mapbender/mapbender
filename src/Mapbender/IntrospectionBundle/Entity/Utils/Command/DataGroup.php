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
     *
     */
    public function toArray()
    {
        $baseValues = parent::toArray();
        if ($this->items) {
            $subValues = array();
            foreach ($this->items as $subItem) {
                $subValues[] = $subItem->toArray();
            }
            return array_replace($baseValues, array(
                'items' => $subValues,
            ));
        } else {
            return $baseValues;
        }
    }
}
