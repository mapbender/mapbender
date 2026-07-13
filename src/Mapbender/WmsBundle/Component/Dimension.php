<?php

namespace Mapbender\WmsBundle\Component;

/**
 * @author Paul Schmidt
 */
class Dimension
{
    const NAME_TIME = 'time';
    const NAME_ELEVATION = 'elevation';
    const NAME_PREFIX = 'dim_';

    /** @var string|null */
    public $name;
    /** @var string|null */
    public $units;
    /** @var string|null */
    public $unitSymbol;
    /** @var string|null */
    public $default;
    /** @var bool */
    public $multipleValues = false;
    /** @var bool */
    public $nearestValue = false;
    /** @var bool */
    public $current = false;
    /** @var string|null */
    public $extent;

    /**
     * @param string|null $value
     */
    public function setName($value): void
    {
        $this->name = $value;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $value
     */
    public function setUnits($value): void
    {
        $this->units = $value;
    }

    /**
     * @return string|null
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * @param string|null $value
     */
    public function setUnitSymbol($value): void
    {
        $this->unitSymbol = $value;
    }

    /**
     * @return string|null
     */
    public function getUnitSymbol()
    {
        return $this->unitSymbol;
    }

    /**
     * @param string|null $value
     */
    public function setDefault($value): void
    {
        $this->default = $value;
    }

    /**
     * @return string|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $value
     */
    public function setMultipleValues($value): void
    {
        $this->multipleValues = !!$value;
    }

    /**
     * @return boolean
     */
    public function getMultipleValues()
    {
        return $this->multipleValues;
    }

    /**
     * @param boolean $value
     */
    public function setNearestValue($value): void
    {
        $this->nearestValue = !!$value;
    }

    /**
     * @return boolean
     */
    public function getNearestValue()
    {
        return $this->nearestValue;
    }

    /**
     * @param boolean $value
     */
    public function setCurrent($value): void
    {
        $this->current = !!$value;
    }

    /**
     * @return boolean
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @param string|null $value
     */
    public function setExtent($value): void
    {
        $this->extent = $value;
    }

    /**
     * @return string|null
     */
    public function getExtent()
    {
        return $this->extent;
    }

    /**
     * Generates a GET parameter name for this dimension.
     * @return string
     */
    public function getParameterName(): string
    {
        if (strtolower((string) $this->name) === self::NAME_TIME) {
            return self::NAME_TIME;
        } elseif (strtolower((string) $this->name) === self::NAME_ELEVATION) {
            return self::NAME_ELEVATION;
        } else {
            return self::NAME_PREFIX . $this->name;
        }
    }
}
