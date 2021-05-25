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
            'element_default_title' => new TwigFunction('element_default_title', array($this, 'element_default_title')),
            'element_title' => new TwigFunction('element_title', array($this, 'element_title')),
            'is_typeof_element_disabled' => new TwigFunction('is_typeof_element_disabled', array($this, 'is_typeof_element_disabled')),
        );
    }

    /**
     * 
     * @param Element $element
     * @return string|null
     */
    public function element_class_title($element)
    {
        return $this->inventoryService->getClassTitle($element->getClass());
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function element_title($element)
    {
        if ($title = $element->getTitle()) {
            return $title;
        } else {
            return $this->element_default_title($element);
        }
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function element_default_title($element)
    {
        return $this->inventoryService->getDefaultTitle($element);
    }

    public function is_typeof_element_disabled(Element $element)
    {
        return $this->inventoryService->isTypeOfElementDisabled($element);
    }
}
