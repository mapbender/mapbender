<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\Exception\UndefinedElementClassException;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Filters / prepares elements for frontend. Works exclusively with Entity\Element
 * (Component\Element = legacy; incompatible with Symfony 4)
 * Default implementation for service mapbender.element_filter
 *
 * @todo; add (guarded vs Symfony debug class loader) class exists checks here
 * @todo: add filter / prepare logic for backend
 */
class ElementFilter extends ElementConfigFilter
{
    /** @var ElementInventoryService */
    protected $inventory;
    /** @var AuthorizationCheckerInterface  */
    protected $authorizationChecker;


    public function __construct(ElementInventoryService $inventory,
                                AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->inventory = $inventory;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return ElementInventoryService
     */
    public function getInventory()
    {
        return $this->inventory;
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function getClassTitle(Element $element)
    {
        $handlingClass = $this->inventory->getHandlingClassName($element);
        if (ClassUtil::exists($handlingClass)) {
            /** @var string|MinimalInterface $handlingClass */
            return $handlingClass::getClassTitle();
        } else {
            return null;
        }
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function getDefaultTitle(Element $element)
    {
        /** @var null|string|MinimalInterface $className */
        $className = $this->inventory->getHandlingClassName($element);
        if ($className && \is_a($className, 'Mapbender\CoreBundle\Element\ControlButton', true)) {
            $target = $element->getTargetElement();
            if ($target && $target !== $element) {
                return $target->getTitle() ?: $this->getDefaultTitle($target);
            }
        }
        if ($className && ClassUtil::exists($className)) {
            return $className::getClassTitle();
        } else {
            return null;
        }
    }

    public function prepareForForm(Element $element)
    {
        $this->migrateConfig($element);
        $canonical = $this->inventory->getCanonicalClassName($element->getClass());
        $element->setClass($canonical);
    }

    /**
     * @param Element[] $elements
     * @param bool $requireGrant
     * @param bool $checkTargets
     * @return Element[]
     */
    public function prepareFrontend($elements, $requireGrant, $checkTargets)
    {
        $elements = $this->filterFrontend($elements, $requireGrant, $checkTargets);
        foreach ($elements as $element) {
            if (!$element->getTitle()) {
                $element->setTitle($this->getDefaultTitle($element));
            }
            $this->migrateConfig($element);
        }
        return $elements;
    }

    /**
     * @param Element[] $elements
     * @param bool $requireGrant
     * @param bool $checkTargets
     * @return Element[]
     */
    public function filterFrontend($elements, $requireGrant, $checkTargets)
    {
        $elementsOut = array();
        foreach ($elements as $element) {
            if ($this->isEnabled($element, $requireGrant, $checkTargets)) {
                $elementsOut[] = $element;
            }
        }
        return $elementsOut;
    }

    /**
     * @param Element $element
     * @return string|MinimalInterface|null
     */
    public function getHandlingClassName(Element $element)
    {
        return $this->inventory->getHandlingClassName($element);
    }

    /**
     * Performs Element class replacements and updates configuration structure to current standards.
     *
     * @param Element $element
     * @throws UndefinedElementClassException
     */
    public function migrateConfig(Element $element)
    {
        $handlingClass = $this->inventory->getHandlingClassName($element);
        $this->migrateConfigInternal($element, $handlingClass);
        // Add config defaults
        /** @var string|MinimalInterface $handlingClass */
        $element->setConfiguration($element->getConfiguration() + $handlingClass::getDefaultConfiguration());
        // Replace class @todo: safe? necessary? Will shred db contents if existing Element is edited
        $element->setClass($handlingClass);
    }

    /**
     * @param Element $element
     * @return bool
     */
    public function isDisabledType(Element $element)
    {
        $disabled = $this->inventory->isClassDisabled($element->getClass());
        if (!$disabled && ($target = $element->getTargetElement())) {
            $disabled = $this->inventory->isClassDisabled($target->getClass());
        }
        return $disabled;
    }

    /**
     * @param Element $element
     * @param bool $checkGrant
     * @param bool $checkTargets
     * @return bool
     */
    protected function isEnabled(Element $element, $checkGrant, $checkTargets)
    {
        $enabled = $element->getEnabled() && !empty($element->getClass()) && !$this->inventory->isClassDisabled($element->getClass());
        if ($enabled) {
            $handlingClass = $this->inventory->getHandlingClassName($element);
            $enabled = ClassUtil::exists($handlingClass) && !$this->inventory->isClassDisabled($handlingClass);
            if ($checkTargets && $enabled && \is_a($handlingClass, 'Mapbender\CoreBundle\Element\ControlButton', true)) {
                $target = $element->getTargetElement();
                $enabled = $target && $this->isEnabled($target, $checkGrant, false);
            }
            if ($checkGrant && $enabled) {
                $enabled = $this->authorizationChecker->isGranted('VIEW', $element);
            }
        }
        return $enabled;
    }
}
