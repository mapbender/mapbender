<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\Element;

/**
 * Maintains inventory of Element Component classes
 *
 * Default implementation for service mapbender.element_inventory.service
 * @since v3.0.8-beta1
 */
class ElementInventoryService
{
    /** @var string[] */
    protected $movedElementClasses = array(
        'Mapbender\CoreBundle\Element\PrintClient' => 'Mapbender\PrintBundle\Element\PrintClient',
        // see https://github.com/mapbender/data-source/tree/0.1.8/Element
        'Mapbender\DataSourceBundle\Element\DataManagerElement' => 'Mapbender\DataManagerBundle\Element\DataManagerElement',
        'Mapbender\DataSourceBundle\Element\DataStoreElement' => 'Mapbender\DataManagerBundle\Element\DataManagerElement',
        'Mapbender\DataSourceBundle\Element\QueryBuilderElement' => 'Mapbender\QueryBuilderBundle\Element\QueryBuilderElement',
        'Mapbender\CoreBundle\Element\Redlining' => 'Mapbender\CoreBundle\Element\Sketch',
    );

    /** @var string[] */
    protected $fullInventory = array();
    /** @var string[] */
    protected $noCreationClassNames = array();
    /** @var string[] */
    protected $disabledClassesFromConfig = array();

    public function __construct($disabledClasses)
    {
        $this->disabledClassesFromConfig = $disabledClasses ?: array();
    }

    /**
     * @param string $classNameIn
     * @return string
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

    /**
     * @param string|MinimalInterface $className
     * @return string|null
     */
    public function getClassTitle($className)
    {
        $adjustedClass = $this->getAdjustedElementClassName($className);
        if (ClassUtil::exists($adjustedClass)) {
            /** @var string|MinimalInterface $adjustedClass */
            return $adjustedClass::getClassTitle();
        } else {
            return null;
        }
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function getDefaultTitle(Element $element)
    {
        /** @var null|string|MinimalInterface $className */
        $className = $this->getAdjustedElementClassName($element->getClass());
        if ($className && \is_a($className, 'Mapbender\CoreBundle\Element\ControlButton', true)) {
            $target = $element->getTargetElement();
            if ($target && $target !== $element) {
                return $target->getTitle() ?: $this->getDefaultTitle($target);
            }
        }
        if ($className && ClassUtil::exists($className)) {
            return $className::getClassTitle();
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
        $this->fullInventory = array_combine($classNames, $classNames);
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
    }

    /**
     * Returns the Element class inventory after taking into account renamed and disabled classes.
     *
     * @return string[]
     */
    public function getActiveInventory()
    {
        $moved = array_intersect_key($this->movedElementClasses, $this->fullInventory);
        $inventoryCopy = $this->fullInventory + array();
        foreach ($moved as $original => $replacement) {
            $inventoryCopy[$original] = $replacement;
        }
        return array_unique(array_diff(array_values($inventoryCopy), $this->noCreationClassNames, $this->getDisabledClasses()));
    }

    /**
     * Returns the full, unmodified Element class inventory advertised by the sum of enabled MapbenderBundles.
     *
     * @return string[]
     */
    public function getRawInventory()
    {
        return array_values($this->fullInventory);
    }

    protected function getDisabledClasses()
    {
        return array_merge($this->disabledClassesFromConfig, $this->getInternallynDisabledClasses());
    }

    public function isClassDisabled($className)
    {
        return \in_array($className, $this->getDisabledClasses());
    }

    public function isTypeOfElementDisabled(Entity\Element $element)
    {
        $disabled = $this->isClassDisabled($element->getClass());
        if (!$disabled && ($target = $element->getTargetElement())) {
            $disabled = $this->isClassDisabled($target->getClass());
        }
        return $disabled;
    }

    protected function getInternallynDisabledClasses()
    {
        return array(
            'Mapbender\WmcBundle\Element\WmcLoader',
            'Mapbender\WmcBundle\Element\WmcList',
            'Mapbender\WmcBundle\Element\WmcEditor',
        );
    }
}
