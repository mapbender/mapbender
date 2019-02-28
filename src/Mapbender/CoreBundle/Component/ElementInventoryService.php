<?php


namespace Mapbender\CoreBundle\Component;


/**
 * Maintains inventory of Element Component classes
 *
 * Registered in container as mapbender.element_inventory.service
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
    );

    /** @var string[] */
    protected $fullInventory = array();
    /** @var string[] */
    protected $noCreationClassNames = array();

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
        return array_unique(array_diff(array_values($inventoryCopy), $this->noCreationClassNames));
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
}
