<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class Dimension
{

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $name;

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $units;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $unitSymbol;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $default;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $multipleValues;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $nearestValue;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $current;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $extent;
    
    /**
     * Set name
     * 
     * @param string $value 
     * @return Dimension
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * Get name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set units
     * 
     * @param string $value 
     * @return Dimension
     */
    public function setUnits($value)
    {
        $this->units = $value;
    }

    /**
     * Get units
     * 
     * @return string
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * Set unitSymbol
     * 
     * @param string $value 
     * @return Dimension
     */
    public function setUnitSymbol($value)
    {
        $this->unitSymbol = $value;
    }

    /**
     * Get unitSymbol
     * 
     * @return string
     */
    public function getUnitSymbol()
    {
        return $this->unitSymbol;
    }

    /**
     * Set default
     * 
     * @param string $value 
     * @return Dimension
     */
    public function setDefault($value)
    {
        $this->default = $value;
    }

    /**
     * Get default
     * 
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set multipleValues
     * 
     * @param boolean $value 
     * @return Dimension
     */
    public function setMultipleValues($value)
    {
        $this->multipleValues = $value;
    }

    /**
     * Get multipleValues
     * 
     * @return boolean
     */
    public function getMultipleValues()
    {
        return $this->multipleValues;
    }

    /**
     * Set nearestValue
     * 
     * @param boolean $value 
     * @return Dimension
     */
    public function setNearestValue($value)
    {
        $this->nearestValue = $value;
    }

    /**
     * Get nearestValue
     * 
     * @return boolean
     */
    public function getNearestValue()
    {
        return $this->nearestValue;
    }

    /**
     * Set current
     * 
     * @param boolean $value 
     * @return Dimension
     */
    public function setCurrent($value)
    {
        $this->current = $value;
    }

    /**
     * Get current
     * 
     * @return boolean
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * Set extent
     * 
     * @param DimExtent $value 
     * @return Dimension
     */
    public function setExtent($value)
    {
        $this->extent = $value;
    }

    /**
     * Get extent
     * 
     * @return DimExtent
     */
    public function getExtent()
    {
        return $this->extent;
    }
    
//
//    public function toArray()
//    {
//        $dimension = array(
//            'name' => $this->name,
//            'units' => $this->units,
//            'unitSymbol' => $this->unitSymbol,
//            'default' => $this->default,
//            'multipleValues' => $this->multipleValues,
//            'nearestValue' => $this->nearestValue,
//            'current' => $this->current,
//            'extent' => $this->extent
//        );
//        return $dimension;
//    }
//
//    public static function fromArray(array $dimension)
//    {
//        $dim = new Dimension();
//        $dim->name = $dimension['name'];
//        $dim->units = $dimension['units'];
//        $dim->unitSymbol = $dimension['unitSymbol'];
//        $dim->default = $dimension['default'];
//        $dim->multipleValues = $dimension['multipleValues'];
//        $dim->nearestValue = isset($dimension['nearestValue']) ? $dimension['nearestValue'] : null;
//        $dim->current = $dimension['current'];
//        $dim->extent = $dimension['extent'];
//        return $dimension;
//    }

}
