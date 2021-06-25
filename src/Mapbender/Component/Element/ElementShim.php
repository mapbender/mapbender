<?php


namespace Mapbender\Component\Element;

use Mapbender\CoreBundle\Component\ElementBase\BoundSelfRenderingInterface;
use Mapbender\CoreBundle\Component\ElementInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * Bridges non-static AbstractElementService API to Component\Element
 */
class ElementShim extends AbstractElementService
    implements ImportAwareInterface
{
    /** @var ElementInterface|BoundSelfRenderingInterface */
    protected $component;
    /** @var ElementHttpShim|null|false */
    protected $httpHandler = null;

    public function __construct(ElementInterface $component)
    {
        $this->component = $component;
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
        if ($this->httpHandler === null) {
            $this->httpHandler = $this->initHttpHandler($this->component) ?: false;
        }
        return $this->httpHandler ?: null;
    }

    /**
     * @param ElementInterface $component
     * @return ElementHttpShim|null
     * @throws \ReflectionException
     */
    protected function initHttpHandler($component)
    {
        if (!($component instanceof \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface)) {
            return null;
        }
        try {
            $refl = new \ReflectionClass($component);
        } catch (\ReflectionException $e) {
            return null;
        }
        $baseClassName = 'Mapbender\CoreBundle\Component\Element';
        /** @see \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface::handleHttpRequest $hasHttp */
        $handlerMethod = $refl->getMethod('handleHttpRequest');
        $declaring = $handlerMethod->getDeclaringClass()->getName();
        $hasHttp = $declaring !== $baseClassName;
        if (!$hasHttp && $refl->hasMethod('httpAction')) {
            $declaring = $refl->getMethod('httpAction')->getDeclaringClass()->getName();
            $hasHttp = $declaring !== $baseClassName;
        }
        if ($hasHttp) {
            return new ElementHttpShim($component);
        } else {
            return null;
        }
    }

    public function onImport(Element $element, Mapper $mapper)
    {
        if ($this->component instanceof \Mapbender\CoreBundle\Component\Element) {
            $configOut = $this->component->denormalizeConfiguration($element->getConfiguration(), $mapper);
            $element->setConfiguration($configOut);
        }
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
