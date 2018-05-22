<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Dimension class.
 *
 * @author Paul Schmidt
 */
class Dimension
{

    const NAME_TIME = 'time';
    const NAME_ELEVATION = 'elevation';
    const NAME_PREFIX = 'dim_';

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
    public $extent;

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
        $this->default = $value;
    }

    /**
     * Get default
     *
     * @return string|null
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
     * Set extent
     *
     * @param string $value
     */
    public function setExtent($value)
    {
        $this->extent = $value;
    }

    /**
     * Get extent
     *
     * @return Dimension
     */
    public function getExtent()
    {
        return $this->extent;
    }

    /**
     * Generates a GET parameter name for this dimension.
     * @return string parameter name
     */
    public function getParameterName()
    {
        if (strtolower($this->name) === self::NAME_TIME) {
            return self::NAME_TIME;
        } elseif (strtolower($this->name) === self::NAME_ELEVATION) {
            return self::NAME_ELEVATION;
        } else {
            return self::NAME_PREFIX . $this->name;
        }
    }
}
