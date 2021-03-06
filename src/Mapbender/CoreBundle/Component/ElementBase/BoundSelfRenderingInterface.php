<?php


namespace Mapbender\CoreBundle\Component\ElementBase;


/**
 * Interface contract for Elements that handle their frontend HTML rendering themselves,
 * with a zero-argument render methods. This necessarily means they will need
 * 1) a bound Element entity
 * 2) a bound reference to the container, or at the very least the twig templating engine
 *
 * @deprecated switch to service type-elements ASAP for Symfony 4+ compatibility
 * @see \Mapbender\Component\Element\AbstractElementService
 * @todo 3.3: remove this interface
 */
interface BoundSelfRenderingInterface extends BoundEntityInterface
{
    /**
     * Render the element HTML fragment.
     *
     * @return string
     */
    public function render();
}
