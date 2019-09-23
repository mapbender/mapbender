<?php

namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Entity\Element;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * ElementExtension
 */
class ElementExtension extends AbstractExtension
{

    /** @var ElementInventoryService */
    protected $inventoryService;

    /**
     * @param ElementInventoryService $inventoryService
     */
    public function __construct(ElementInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mapbender_element';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return array(
            'element_class_title' => new TwigFunction('element_class_title', array($this, 'element_class_title')),
        );
    }

    /**
     * 
     * @param Element $element
     * @return string
     */
    public function element_class_title($element)
    {
        $initialClass = $element->getClass();
        $adjustedClass = $this->inventoryService->getAdjustedElementClassName($initialClass);
        if (class_exists($adjustedClass)) {
            return $adjustedClass::getClassTitle();
        }
    }
}

