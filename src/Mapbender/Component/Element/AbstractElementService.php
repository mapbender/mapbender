<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Entity\Element;

abstract class AbstractElementService
    implements ElementServiceInterface, HttpHandlerProvider

{
    /**
     * @param Element $element
     * @return array
     */
    public function getClientConfiguration(Element $element)
    {
        return $element->getConfiguration() ?: array();
    }

    /**
     * @param Element $element
     * @return ElementHttpHandlerInterface|null
     */
    public function getHttpHandler(Element $element)
    {
        return null;
    }
}
