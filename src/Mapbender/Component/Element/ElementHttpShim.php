<?php


namespace Mapbender\Component\Element;

use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;

class ElementHttpShim implements ElementHttpHandlerInterface
{
    /** @var \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface */
    protected $component;

    public function __construct(\Mapbender\CoreBundle\Component\ElementHttpHandlerInterface $component)
    {
        $this->component = $component;
    }

    public function handleRequest(Element $element, Request $request)
    {
        return $this->component->handleHttpRequest($request);
    }
}
