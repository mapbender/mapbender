<?php


namespace Mapbender\CoreBundle\Component;

use Mapbender\Component\BaseElementFactory;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Component;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Factory service providing preinitialized Element entities and Element components.
 * Instance registered at mapbender.element_factory.service
 */
class ElementFactory extends BaseElementFactory
{
    /** @var Element[] */
    protected $componentDummies = array();
    /** @var Application */
    protected $applicationComponentDummy;
    /** @var ContainerInterface */
    protected $container;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var Component\Element[] */
    protected $components = array();
    /** @var Component\Application[] */
    protected $appComponents = array();

    /**
     * @param ElementInventoryService $inventoryService
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container only used for passing on to Element/Application component constructors
     */
    public function __construct(ElementInventoryService $inventoryService,
                                TranslatorInterface $translator,
                                ContainerInterface $container)
    {
        parent::__construct($inventoryService);
        $this->translator = $translator;
        $this->container = $container;
        $this->applicationComponentDummy = $this->appComponentFromEntity(new Entity\Application());
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
            if ($entity->getApplication()) {
                $appComponent = $this->appComponentFromEntity($entity->getApplication(), $reuse);
            } else {
                $appComponent = $this->applicationComponentDummy;
            }
            $instance = $this->instantiateComponent($entity, $appComponent);
            $this->components[$entityObjectId] = $instance;
        }
        return $this->components[$entityObjectId];
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
            $element = new Entity\Element();
            $element->setClass($className);
            $dummy = $this->instantiateComponent($element, $this->applicationComponentDummy);
            $this->componentDummies[$className] = $dummy;
        }
        return $this->componentDummies[$className];
    }

    /**
     * @param Entity\Element $entity
     * @param Application $appComponent
     * @return Element
     * @throws Component\Exception\ElementErrorException
     */
    protected function instantiateComponent(Entity\Element $entity, Application $appComponent)
    {
        $finalClassName = $this->inventoryService->getAdjustedElementClassName($entity->getClass());
        $entity->setClass($finalClassName);
        // The class_exists call itself may throw, depending on Composer version and promotion of warnings to
        // Exceptions via Symfony.
        try {
            if (!class_exists($finalClassName, true)) {
                throw new Component\Exception\UndefinedElementClassException($finalClassName);
            }
        } catch (\Exception $e) {
            throw new Component\Exception\UndefinedElementClassException($finalClassName, $e);
        }
        $this->migrateElementConfiguration($entity);
        $instance = new $finalClassName($appComponent, $this->container, $entity);
        if (!$instance instanceof Component\Element) {
            throw new Component\Exception\InvalidElementClassException($finalClassName);
        }
        // @todo: check API conformance and generate deprecation warnings via trigger_error(E_USER_DEPRECATED, ...)
        return $instance;
    }

    /**
     * @param Entity\Application $appEntity
     * @param bool $reuse
     * @return Component\Application
     */
    public function appComponentFromEntity(Entity\Application $appEntity, $reuse=true)
    {
        $entityObjectId = spl_object_hash($appEntity);
        if (!$reuse || !array_key_exists($entityObjectId, $this->appComponents)) {
            $instance = new Component\Application($this->container, $appEntity);
            $this->appComponents[$entityObjectId] = $instance;
        }
        return $this->appComponents[$entityObjectId];
    }
}
