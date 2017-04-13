<?php

namespace Mapbender\WmsBundle\Component;

/**
 * MinMax class.
 *
 * @author Paul Schmidt
 */
class MinMax
{
    /**
     * ORM\Column(type="float", nullable=true)
     */
    public $min;
    /**
     * ORM\Column(type="float", nullable=true)
     */
    public $max;

    /**
     *
     * @param float | null $min min value
     * @param float | null $max max value
     */
    public function __construct($min = null, $max = null)
    {
        $this->setMin($min);
        $this->setMax($max);
    }

    /**
     * Get min
     *
     * @return float
     */
    public function getMin()
    {
        $value = $this->min;
        if ($value == INF) {
            $value = null;
        }
        return $value === null ? null : floatval($value);
    }

    /**
     * Set min
     * @param float|null $value
     * @return $this
     */
    public function setMin($value)
    {
        $this->min = $value === null ? null : floatval($value);
        return $this;
    }

    /**
     * Get max
     *
     * @return float|null
     */
    public function getMax()
    {
        $value = $this->max;
        if ($value == INF) {
            $value = null;
        }
        return $value === null ? null : floatval($value);
    }

    /**
     * Set max
     *
     * @param float|null $value
     * @return $this
     */
    public function setMax($value)
    {
        $this->max = $value === null ? null : floatval($value);
        return $this;
    }

    public function getInRange($value)
    {
        $value_ = $value;
        $value_ = $this->min !== null ? ($value_ && $value_ < $this->min ? $this->min : $value_) : $value_;
        $value_ = $this->max !== null ? ($value_ && $value_ > $this->max ? $this->max : $value_) : $value_;
        return $value_ === null ? null : floatval($value_);
    }

    public static function create($value1, $value2)
    {
        if (null === $value1) {
            return new MinMax(null, $value2);
        } elseif (null === $value2) {
            return new MinMax(null, $value1);
        } elseif ($value2 > $value1) {
            return new MinMax($value1, $value2);
        } elseif ($value2 < $value1) {
            return new MinMax($value2, $value1);
        } else {
            return new MinMax(null, null);
        }
    }
}