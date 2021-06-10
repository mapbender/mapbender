<?php


namespace Mapbender\Component\Element;

use Mapbender\CoreBundle\Component\ElementBase\BoundSelfRenderingInterface;
use Mapbender\CoreBundle\Component\ElementInterface;
use Mapbender\CoreBundle\Entity\Element;

/**
 * Bridges non-static AbstractElementService API to Component\Element
 */
class ElementShim extends AbstractElementService
{
    /** @var ElementInterface|BoundSelfRenderingInterface */
    protected $component;
    /** @var ElementHttpShim|null */
    protected $httpHandler;

    public function __construct(ElementInterface $component)
    {
        $this->component = $component;
        if ($component instanceof \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface) {
            $this->httpHandler = new ElementHttpShim($component);
        }
    }

    public function getWidgetName(Element $element)
    {
        return $this->component->getWidgetName();
    }

    public function getClientConfiguration(Element $element)
    {
        return $this->component->getPublicConfiguration();
    }

    public function getRequiredAssets(Element $element)
    {
        return $this->component->getAssets();
    }

    public function getView(Element $element)
    {
        return new LegacyView($this->component->render());
    }

    public function getHttpHandler(Element $element)
    {
        return $this->httpHandler;
    }

    /** @todo: split service element interface to not contain any static methods */
    public static function getType()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getFormTemplate()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getDefaultConfiguration()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getClassTitle()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getClassDescription()
    {
        throw new \LogicException("Cannot access shim method statically");
    }
}
