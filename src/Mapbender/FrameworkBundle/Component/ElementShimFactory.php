<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementShim;
use Mapbender\CoreBundle\Component\ElementInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidElementClassException;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ElementShimFactory
{
    /** @var ContainerInterface|null */
    protected $container;

    /** @var array<ElementShim|null> */
    protected $instances = array();

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Element $element
     * @return AbstractElementService|null
     */
    public function getShim(Element $element)
    {
        $key = \spl_object_id($element);
        if (!\array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $this->createShim($element);
        }
        return $this->instances[$key] ?: null;
    }

    protected function createShim(Element $element)
    {
        $componentClassName = $element->getClass();
        $component = new $componentClassName($this->container, $element);
        if (!$component instanceof ElementInterface) {
            throw new InvalidElementClassException($componentClassName);
        }
        return new ElementShim($component);
    }
}
