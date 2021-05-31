<?php


namespace Mapbender\Component;


use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Entity\Element;

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

    /**
     * @param Element $element
     * @return string|\Mapbender\CoreBundle\Component\Element
     */
    protected function getComponentClass(Element $element)
    {
        return $this->inventoryService->getAdjustedElementClassName($element->getClass());
    }
}
