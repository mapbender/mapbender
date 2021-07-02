<?php


namespace Mapbender\CoreBundle\Component\ElementBase;


use Mapbender\CoreBundle\Entity;

/**
 * Minimal Element with a bound entity.
 *
 * @deprecated switch to service type-elements ASAP for Symfony 4+ compatibility
 * @see \Mapbender\Component\Element\AbstractElementService
 * @todo 3.3: remove this interface
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
     * Returns $this->entity->getId()
     * @return string
     * @deprecated
     */
    final public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Returns $this->entity->getTitle()
     * @return string
     * @deprecated
     */
    final public function getTitle()
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
