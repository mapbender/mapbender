<?php


namespace Mapbender\Component;


use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\Exception\UndefinedElementClassException;
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
     * Updates legacy values in Element's $configuration attribute (YAML applications, old database installs) to
     * current standards.
     * Also updates legacy $class values.
     *
     * @param Element $element
     */
    public function migrateElementConfiguration(Element $element)
    {
        if (!$element->getClass()) {
            throw new \LogicException("Empty component class name on element");
        }
        $componentClassName = $this->inventoryService->getAdjustedElementClassName($element->getClass());
        // The class_exists call itself may throw, depending on Composer version and promotion of warnings to
        // Exceptions via Symfony.
        try {
            if (!class_exists($componentClassName, true)) {
                throw new UndefinedElementClassException($componentClassName);
            }
        } catch (\Exception $e) {
            throw new UndefinedElementClassException($componentClassName, null, $e);
        }
        $element->setClass($componentClassName);

        if (is_a($componentClassName, 'Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface', true)) {
            /** @var \Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface $componentClassName */
            // Avoid trying to migrate unconfigured elements further
            // This allows updateEntityConfig implementations to skip past a bunch of array_key_exists and similar
            // checks, which would otherwise be necessary on newly created and dummy entities
            if ($element->getConfiguration()) {
                $componentClassName::updateEntityConfig($element);
            }
        }
    }
}
