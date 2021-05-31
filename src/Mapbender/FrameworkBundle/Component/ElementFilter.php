<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\CoreBundle\Component\ElementInventoryService;
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
}
