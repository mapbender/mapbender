<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\Element\ElementServiceFrontendInterface;
use Mapbender\Component\Element\ElementServiceInterface;
use Mapbender\Component\Element\HttpHandlerProvider;
use Mapbender\Component\Element\ImportAwareInterface;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\FrameworkBundle\Component\ElementConfigFilter;
use Mapbender\FrameworkBundle\Component\ElementShimFactory;

/**
 * Maintains inventory of Element Component classes
 *
 * Default implementation for service mapbender.element_inventory.service
 * @since v3.0.8-beta1
 */
class ElementInventoryService extends ElementConfigFilter implements HttpHandlerProvider
{
    /** @var string[] */
    protected $movedElementClasses = array(
        // see https://github.com/mapbender/data-source/tree/0.1.8/Element
        'Mapbender\DataSourceBundle\Element\DataManagerElement' => 'Mapbender\DataManagerBundle\Element\DataManagerElement',
        'Mapbender\DataSourceBundle\Element\DataStoreElement' => 'Mapbender\DataManagerBundle\Element\DataManagerElement',
        'Mapbender\DataSourceBundle\Element\QueryBuilderElement' => 'Mapbender\QueryBuilderBundle\Element\QueryBuilderElement',
    );

    protected $inventoryDirty = true;
    /** @var string[] */
    protected $legacyInventory = array();
    /** @var string[] */
    protected $fullInventory = array();
    /** @var string[] */
    protected $activeInventory = array();
    /** @var string[] */
    protected $canonicals = array();
    /** @var string[] */
    protected $noCreationClassNames = array();
    /** @var string[] */
    protected $disabledClassesFromConfig = array();
    /** @var ElementServiceInterface[] */
    protected $serviceElements = array();
    /** @var ElementShimFactory|null */
    protected $shimFactory;

    public function __construct($disabledClasses,
                                ElementShimFactory $shimFactory = null)
    {
        $this->disabledClassesFromConfig = $disabledClasses ?: array();
        $this->shimFactory = $shimFactory;
    }

    /**
     * @param string $classNameIn
     * @return string|MinimalInterface
     * @deprecated prefer getHandlingClassName (requires Element argument)
     */
    public function getAdjustedElementClassName($classNameIn)
    {
        if (!empty($this->movedElementClasses[$classNameIn])) {
            $classNameOut = $this->movedElementClasses[$classNameIn];
            return $classNameOut;
        } else {
            return $classNameIn;
        }
    }

    public function getHandlingClassName(Element $element)
    {
        $handlingClass = parent::getHandlingClassName($element);
        if (!empty($this->movedElementClasses[$handlingClass])) {
            $handlingClass = $this->movedElementClasses[$handlingClass];
        }
        return $handlingClass;
    }

    public function getCanonicalClassName($classNameIn)
    {
        if (!empty($this->canonicals[$classNameIn])) {
            return $this->canonicals[$classNameIn];
        } else {
            return $classNameIn;
        }
    }

    /**
     * @param Element $element
     * @return ElementServiceInterface|null
     */
    public function getHandlerService(Element $element)
    {
        return $this->getHandlerServiceInternal($element, false);
    }

    /**
     * @param Element $element
     * @return \Mapbender\Component\Element\ElementHttpHandlerInterface|null
     */
    public function getHttpHandler(Element $element)
    {
        // Assumes prepareFrontend has already updated class; see ApplicationController::elementAction
        $handler = $this->getHandlerServiceInternal($element, true);
        if ($handler && ($handler instanceof HttpHandlerProvider)) {
            return $handler->getHttpHandler($element);
        } else {
            return null;
        }
    }

    /**
     * @param Element $element
     * @return ElementServiceFrontendInterface|null
     */
    public function getFrontendHandler(Element $element)
    {
        // Assumes prepareFrontend has already updated class; see ApplicationController::elementAction
        return $this->getHandlerServiceInternal($element, true);
    }

    /**
     * @param Element $element
     * @return ImportAwareInterface|null
     */
    public function getImportProcessor(Element $element)
    {
        $handler = $this->getHandlerServiceInternal($element, true);
        if ($handler && ($handler instanceof ImportAwareInterface)) {
            return $handler;
        } else {
            return null;
        }
    }

    /**
     * @param string[] $classNames
     */
    public function setInventory($classNames)
    {
        // map value:value to ease array_intersect_key in getActiveInventory
        $this->legacyInventory = array_combine($classNames, $classNames);
        $this->inventoryDirty = true;
    }

    /**
     * @param ElementServiceInterface $instance
     * @param string[] $handledClassNames
     * @param string|null $canonical
     * @noinspection PhpUnused
     * @see \Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterElementServicesPass::process()
     */
    public function registerService(ElementServiceInterface $instance, $handledClassNames, $canonical = null)
    {
        $this->registerServices(array(array(
            $instance,
            $handledClassNames,
            $canonical ?: \get_class($instance),
        )));
    }

    /**
     * Bulk service registration
     *
     * @param array[] $serviceInfoList
     *
     * Each entry array must contain
     *   0 => the service instance (object)
     *   1 => handled class names (string[])
     *   2 => canonical name (string)
     */
    public function registerServices(array $serviceInfoList)
    {
        foreach ($serviceInfoList as $serviceInfo) {
            $instance = $serviceInfo[0];
            $handledClassNames = $serviceInfo[1];
            $canonical = $serviceInfo[2];

            $serviceClass = \get_class($instance);
            $handledClassNames = array_diff($handledClassNames, $this->getDisabledClasses());
            foreach (array_unique($handledClassNames) as $handledClassName) {
                $this->serviceElements[$handledClassName] = $instance;
                if ($handledClassName !== $serviceClass) {
                    $this->replaceElement($handledClassName, $serviceClass);
                }
                $this->canonicals[$handledClassName] = $canonical ?: $serviceClass;
            }
            if ($canonical !== $serviceClass && false === \array_search($canonical, $handledClassNames)) {
                $this->replaceElement($canonical, $serviceClass);
            }
        }
        $this->inventoryDirty = true;
    }

    /**
     * Marks $classNameTo as the acting replacement for $classNameFrom.
     *
     * @param string $classNameFrom
     * @param string $classNameTo
     */
    public function replaceElement($classNameFrom, $classNameTo)
    {
        if (!$classNameFrom || !$classNameTo) {
            throw new \InvalidArgumentException("Empty class name");
        }
        if ($classNameFrom == $classNameTo) {
            throw new \LogicException("Cannot replace {$classNameFrom} with itself");
        }
        // support layered replacements
        while ($inMoved = array_search($classNameFrom, $this->movedElementClasses)) {
            $this->movedElementClasses[$inMoved] = $classNameTo;
        }
        $this->movedElementClasses[$classNameFrom] = $classNameTo;
        $circular = array_intersect(array_values($this->movedElementClasses), array_keys($this->movedElementClasses));
        if ($circular) {
            throw new \LogicException("Circular class replacement detected for " . implode(', ', $circular));
        }
        $this->inventoryDirty = true;
    }

    /**
     * Disables the Element of given $className insofar that it can no longer be added to
     * any applications.
     *
     * @param string $className
     */
    public function disableElementCreation($className)
    {
        if (!$className) {
            throw new \InvalidArgumentException("Class name empty");
        }
        $this->noCreationClassNames[] = $className;
        $this->noCreationClassNames = array_unique(array_merge($this->noCreationClassNames));
        $this->inventoryDirty = true;
    }

    /**
     * Returns the Element class inventory after taking into account renamed and disabled classes.
     *
     * @return string[]
     */
    public function getActiveInventory()
    {
        $this->resolveInventory();
        return $this->activeInventory;
    }

    /**
     * Returns the full, unmodified Element class inventory advertised by the sum of enabled MapbenderBundles.
     *
     * @return string[]
     */
    public function getRawInventory()
    {
        $this->resolveInventory();
        return array_values($this->fullInventory);
    }

    protected function getDisabledClasses()
    {
        return array_merge($this->disabledClassesFromConfig, $this->getInternallyDisabledClasses());
    }

    public function isClassDisabled($className)
    {
        return \in_array($className, $this->getDisabledClasses());
    }

    protected function getInternallyDisabledClasses()
    {
        return array(
            'Mapbender\WmcBundle\Element\WmcLoader',
            'Mapbender\WmcBundle\Element\WmcList',
            'Mapbender\WmcBundle\Element\WmcEditor',
        );
    }

    /**
     * @param Element $element
     * @param bool $allowShim
     * @return ElementServiceInterface|null
     */
    protected function getHandlerServiceInternal(Element $element, $allowShim = false)
    {
        $className = $this->getHandlingClassName($element);

        if (!empty($this->serviceElements[$className])) {
            return $this->serviceElements[$className];
        } elseif ($allowShim && $this->shimFactory) {
           return $this->shimFactory->getShim($className);
        } else {
            return null;
        }
    }

    protected function resolveInventory()
    {
        if ($this->inventoryDirty) {
            $this->fullInventory = $this->legacyInventory;
            foreach (array_keys($this->serviceElements) as $serviceClass) {
                $this->fullInventory[$serviceClass] = $serviceClass;
            }
            $moved = array_intersect_key($this->movedElementClasses, $this->fullInventory);
            $active = $this->fullInventory;
            foreach ($moved as $original => $replacement) {
                $active[$original] = $replacement;
            }
            $active = array_diff(array_values($active), $this->noCreationClassNames, $this->getDisabledClasses());
            $this->activeInventory = array_unique($active);
        }
    }
}
