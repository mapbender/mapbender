<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementShim;
use Mapbender\CoreBundle\Component\ElementInterface;
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
     * @param string $handlingClass
     * @return AbstractElementService|null
     */
    public function getShim($handlingClass)
    {
        if (!\array_key_exists($handlingClass, $this->instances)) {
            $this->instances[$handlingClass] = $this->createShim($handlingClass);
        }
        return $this->instances[$handlingClass] ?: null;
    }

    /**
     * @param string|ElementInterface $className
     * @return ElementShim
     */
    protected function createShim($className)
    {
        @trigger_error("DEPRECATED: Legacy Element class {$className} is incompatible with Symfony 4+. Support will be removed in Mapbender 3.3. Inherit from AbstractElementService instead.", E_USER_DEPRECATED);
        return new ElementShim($this->container, $className);
    }
}
