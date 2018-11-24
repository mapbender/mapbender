<?php


namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Factory service providing preinitialized Element entities and Element components.
 * Instance registered at mapbender.element_factory.service
 */
class ElementFactory
{
    /** @var Element[] */
    protected $componentDummies = array();
    /** @var Application */
    protected $applicationComponentDummy;
    /** @var ContainerInterface */
    protected $container;
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container only used for passing on to Element/Application component constructors
     */
    public function __construct(TranslatorInterface $translator, ContainerInterface $container)
    {
        $this->applicationComponentDummy = new Application($container, new Entity\Application());
        $this->translator = $translator;
        $this->container = $container;
    }

    /**
     * @param $componentClass
     * @param $region
     * @param Entity\Application|null $application
     * @return Entity\Element
     */
    public function newEntity($componentClass, $region, Entity\Application $application = null)
    {
        $entity = new Entity\Element();
        $component = $this->getComponentDummy($componentClass);
        $configuration = $component->getDefaultConfiguration();
        $entity
            ->setClass($componentClass)
            ->setRegion($region)
            ->setWeight(0)
            ->setTitle($this->translator->trans($component->getClassTitle()))
            ->setConfiguration($configuration)
        ;
        if ($application) {
            $entity->setApplication($application);
        }
        return $entity;
    }

    /**
     * @param string $className
     * @return Element
     */
    protected function getComponentDummy($className)
    {
        if (!array_key_exists($className, $this->componentDummies)) {
            $dummy = $this->instantiateComponent($className, new Entity\Element(), $this->applicationComponentDummy);
            $this->componentDummies[$className] = $dummy;
        }
        return $this->componentDummies[$className];
    }

    /**
     * @param $className
     * @param Entity\Element $entity
     * @param Application $appComponent
     * @return Element
     */
    public function instantiateComponent($className, Entity\Element $entity, Application $appComponent)
    {
        // @todo: throw custom exception if !class_exists($className)
        // @todo: throw custom exception if !$instance instanceof Element
        // @todo: check API conformance and generate deprecation warnings via trigger_error(E_USER_DEPRECATED, ...)
        $instance = new $className($appComponent, $this->container, $entity);
        return $instance;
    }
}
