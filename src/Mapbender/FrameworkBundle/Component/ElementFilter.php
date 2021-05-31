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
class ElementFilter
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

    public function prepareForForm(Element $element)
    {
        // @todo: prevent replacement of persisted class (shreds db content permanently)
        $this->migrateConfig($element);
    }

    /**
     * @param Element[] $elements
     * @param bool $requireGrant
     * @return Element[]
     */
    public function prepareFrontend($elements, $requireGrant)
    {
        $elements = $this->filterFrontend($elements, $requireGrant);
        foreach ($elements as $element) {
            if (!$element->getTitle()) {
                $element->setTitle($this->inventory->getDefaultTitle($element));
            }
            $this->migrateConfig($element);
        }
        return $elements;
    }

    /**
     * @param Element[] $elements
     * @param bool $requireGrant
     * @return Element[]
     */
    public function filterFrontend($elements, $requireGrant)
    {
        $elementsOut = array();
        foreach ($elements as $element) {
            $enabled = $element->getEnabled() && !$this->inventory->isTypeOfElementDisabled($element);
            if ($enabled && (!$requireGrant || $this->authorizationChecker->isGranted('VIEW', $element))) {
                $elementsOut[] = $element;
            }
        }
        return $elementsOut;
    }

    /**
     * Performs Element class replacements and updates configuration structure to current standards.
     *
     * @param Element $element
     * @throws UndefinedElementClassException
     */
    public function migrateConfig(Element $element)
    {
        $handlingClass = $this->inventory->getAdjustedElementClassName($element->getClass());
        if (!ClassUtil::exists($handlingClass)) {
            throw new UndefinedElementClassException($handlingClass);
        }
        while (\is_a($handlingClass, 'Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface', true)) {
            // Update config
            $classBefore = $handlingClass;
            /** @var string|\Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface $handlingClass */
            $handlingClass::updateEntityConfig($element);
            $handlingClass = $element->getClass();
            if ($handlingClass === $classBefore) {
                break;
            }
        }
        // Add config defaults
        /** @var string|MinimalInterface $handlingClass */
        $element->setConfiguration($element->getConfiguration() + $handlingClass::getDefaultConfiguration());
        // Replace class @todo: safe? necessary? Will shred db contents if existing Element is edited
        $element->setClass($handlingClass);
    }
}
