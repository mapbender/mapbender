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
     * @param string $className
     * @return AbstractElementService|null
     */
    public function getShim(Element $element, $className)
    {
        $key = \spl_object_id($element) . $className;

        if (!\array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $this->createShim($element, $className);
        }
        return $this->instances[$key] ?: null;
    }

    /**
     * @param Element $element
     * @param string|ElementInterface $className
     * @return ElementShim
     */
    protected function createShim(Element $element, $className)
    {
        $component = new $className($this->container, $element);
        if (!$component instanceof ElementInterface) {
            throw new InvalidElementClassException($className);
        }
        @trigger_error("DEPRECATED: Legacy Element class {$className} is incompatible with Symfony 4+. Support will be removed in Mapbender 3.3. Inherit from AbstractElementService instead.", E_USER_DEPRECATED);

        return new ElementShim($component);
    }
}
