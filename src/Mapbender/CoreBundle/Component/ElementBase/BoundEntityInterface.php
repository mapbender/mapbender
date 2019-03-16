<?php


namespace Mapbender\CoreBundle\Component\ElementBase;

/**
 * Interface for Element components that are bound to an Element entity in their constructors.
 * Currently, this is every Mapbender Element.
 * Long-term, we want to be able to support unbound, service-style handling of elements.
 */
interface BoundEntityInterface
{
    /**
     * Must return getEntity()->getId()
     * May be invoked magically from certain element twig templates.
     * @return string
     */
    public function getId();

    /**
     * Override hook. Should mostly be equivalent to getEntity()->getTitle()
     * May be invoked magically from certain element twig templates.
     * @return string
     */
    public function getTitle();

    /**
     * Used as a crutch in application config generation to reextract the (protected) Element entity
     * out of an Element component.
     * May also be invoked magically from certain element twig templates.
     * @return \Mapbender\CoreBundle\Entity\Element $entity
     */
    public function getEntity();
}
