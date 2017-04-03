<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimTime extends Dimension
{

    /**
     * ORM\Column(type="string", nullable=false)
     */
    public $name;

    /**
     * ORM\Column(type="string", nullable=false)
     */
    public $units;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $unitSymbol;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $default;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $multipleValues = false;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $nearestValue = false;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    public $current = false;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $extentValue;

    /**
     * Set name
     *
     * @param string $value
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
     */
    public function setDefault($value)
    {
        if (is_bool($value)) {
            $this->default = $value;
        } else {
            $this->default = (boolean) $value;
        }
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
     */
    public function setMultipleValues($value)
    {
        if (is_bool($value)) {
            $this->multipleValues = $value;
        } else {
            $this->multipleValues = (boolean) $value;
        }
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
     */
    public function setNearestValue($value)
    {
        if (is_bool($value)) {
            $this->nearestValue = $value;
        } else {
            $this->nearestValue = (boolean) $value;
        }
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
     */
    public function setCurrent($value)
    {
        if (is_bool($value)) {
            $this->current = $value;
        } else {
            $this->current = (boolean) $value;
        }
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
     * Set extentValue
     *
     * @param string $value
     */
    public function setExtentValue($value)
    {
        $this->extentValue = $value;
    }

    /**
     * Get extentValue
     *
     * @return string
     */
    public function getExtentValue()
    {
        return $this->extentValue;
    }
}
