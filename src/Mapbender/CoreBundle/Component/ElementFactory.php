<?php


namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Component;
use Mapbender\CoreBundle\Component\Exception\InvalidElementClassException;
use Mapbender\FrameworkBundle\Component\ElementEntityFactory;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Factory service providing preinitialized Element entities and Element components.
 * Instance registered at mapbender.element_factory.service
 */
class ElementFactory extends ElementEntityFactory
{
    /** @var ContainerInterface */
    protected $container;
    /** @var Component\Element[] */
    protected $components = array();

    /**
     * @param ElementFilter $elementFilter
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container only used for passing on to Element/Application component constructors
     */
    public function __construct(ElementFilter $elementFilter,
                                TranslatorInterface $translator,
                                ContainerInterface $container)
    {
        parent::__construct($elementFilter, $translator);
        $this->container = $container;
    }

    /**
     * @param Entity\Element $entity
     * @param bool $reuse to return the same instance again if both entites are the same (via spl object hash)
     * @return Component\Element
     * @throws Component\Exception\ElementErrorException
     */
    public function componentFromEntity(Entity\Element $entity, $reuse=true)
    {
        $entityObjectId = spl_object_hash($entity);
        if (!$reuse || !array_key_exists($entityObjectId, $this->components)) {
            $instance = $this->instantiateComponent($entity);
            $this->components[$entityObjectId] = $instance;
        }
        return $this->components[$entityObjectId];
    }

    /**
     * @param Entity\Element $entity
     * @return Element
     * @throws Component\Exception\ElementErrorException
     */
    protected function instantiateComponent(Entity\Element $entity)
    {
        $this->elementFilter->migrateConfig($entity);
        $componentClassName = $entity->getClass();
        $instance = new $componentClassName($this->container, $entity);
        if (!$instance instanceof Component\Element) {
            throw new InvalidElementClassException($componentClassName);
        }
        // @todo: check API conformance and generate deprecation warnings via trigger_error(E_USER_DEPRECATED, ...)
        return $instance;
    }
}
