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

    public function migrateElementConfiguration(Element $element)
    {
        if (!$element->getClass()) {
            throw new \LogicException("Empty component class name on element");
        }

        $componentClassName = $this->inventoryService->getAdjustedElementClassName($element->getClass());
        if (is_a($componentClassName, 'Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface', true)) {
            /** @var \Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface $componentClassName */
            $componentClassName::updateEntityConfig($element);
        }
    }
}
