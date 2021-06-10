<?php


namespace Mapbender\Component;


use Mapbender\CoreBundle\Component\ElementInventoryService;


class BaseElementFactory
{
    /** @var ElementInventoryService */
    protected $inventoryService;

    public function __construct(ElementInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * @return ElementInventoryService
     */
    public function getInventory()
    {
        return $this->inventoryService;
    }
}
