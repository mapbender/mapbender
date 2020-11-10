<?php


namespace Mapbender\CoreBundle\Component\ElementBase;


use Mapbender\CoreBundle\Entity;

/**
 * Minimal Element with a bound entity.
 */
abstract class MinimalBound implements BoundEntityInterface
{
    /** @var Entity\Element */
    protected $entity;

    protected function __construct(Entity\Element $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Must return getEntity()->getId()
     * May be invoked magically from certain element twig templates.
     * @return string
     */
    final public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Override hook. Default implementation is ->getEntity()->getTitle()
     * May be invoked magically from certain element twig templates.
     * @return string
     */
    public function getTitle()
    {
        return $this->entity->getTitle();
    }

    /**
     * Used as a crutch in application config generation to reextract the (protected) Element entity
     * out of an Element component.
     * May also be invoked magically from certain element twig templates.
     * @return \Mapbender\CoreBundle\Entity\Element $entity
     */
    final public function getEntity()
    {
        return $this->entity;
    }
}
