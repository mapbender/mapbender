<?php


namespace Mapbender\CoreBundle\Component;

use Mapbender\Component\BaseElementFactory;
use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Component;
use Mapbender\CoreBundle\Component\Exception\InvalidElementClassException;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Factory service providing preinitialized Element entities and Element components.
 * Instance registered at mapbender.element_factory.service
 */
class ElementFactory extends BaseElementFactory
{
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var ContainerInterface */
    protected $container;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var Component\Element[] */
    protected $components = array();

    /**
     * @param ElementInventoryService $inventoryService
     * @param ElementFilter $elementFilter
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container only used for passing on to Element/Application component constructors
     */
    public function __construct(ElementInventoryService $inventoryService,
                                ElementFilter $elementFilter,
                                TranslatorInterface $translator,
                                ContainerInterface $container)
    {
        parent::__construct($inventoryService);
        $this->elementFilter = $elementFilter;
        $this->translator = $translator;
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
     * @param $componentClass
     * @param $region
     * @param Entity\Application|null $application
     * @return Entity\Element
     */
    public function newEntity($componentClass, $region, Entity\Application $application = null)
    {
        /** @var string|Component\ElementBase\MinimalInterface $componentClass */
        $componentClass = $this->inventoryService->getAdjustedElementClassName($componentClass);
        if (!$componentClass || !ClassUtil::exists($componentClass)) {
            throw new Component\Exception\UndefinedElementClassException($componentClass);
        }

        $entity = new Entity\Element();
        $configuration = $componentClass::getDefaultConfiguration();
        $entity
            ->setClass($componentClass)
            ->setRegion($region)
            ->setWeight(0)
            ->setConfiguration($configuration)
        ;
        if (!$componentClass || !\is_a($componentClass, 'Mapbender\CoreBundle\Element\ControlButton')) {
            // Leave title empty. Will be resolved to target title when rendering
            // @todo: make title column nullable (will require schema update)
            $entity->setTitle('');
        } else {
            // @todo: reevaluate translation; translation should be done on presentation, not persisted
            $entity->setTitle($this->translator->trans($this->elementFilter->getDefaultTitle($entity)));
        }
        if ($application) {
            $entity->setApplication($application);
        }
        return $entity;
    }

    /**
     * (Re)Configure an element entity based on array-style configuration. Used in Yaml-defined applications.
     *
     * @param Entity\Element $element
     * @param mixed[] $configuration
     */
    public function configureElement(Entity\Element $element, $configuration)
    {
        $element->setConfiguration($configuration);
        /** @var string|Component\ElementBase\MinimalInterface $componentClass */
        $componentClass = $this->inventoryService->getAdjustedElementClassName($element->getClass());

        // Do not use original $configuration array. Configuration may already have been modified once implicitly.
        /** @see ConfigMigrationInterface */
        $defaults = $componentClass::getDefaultConfiguration();
        $configInitial = $element->getConfiguration();
        $mergedConfig = array_replace($defaults, array_filter($configInitial, function($v) {
            return $v !== null;
        }));
        // Quirks mode: add back NULL values where the defaults didn't even have the corresponding key
        foreach (array_keys($configInitial) as $key) {
            if (!array_key_exists($key, $mergedConfig)) {
                assert($configInitial[$key] === null);
                $mergedConfig[$key] = null;
            }
        }
        $element->setConfiguration($mergedConfig);
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
