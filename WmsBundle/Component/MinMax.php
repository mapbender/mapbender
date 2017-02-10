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
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $min = null;
    /**
     * ORM\Column(type="float", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $max = null;

    /**
     *
     * @param float | null $min min value
     * @param float | null $max max value
     */
    public function __construct($min = null, $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Get min
     *
     * @return float
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set min
     * @param float $min
     * @return MinMax
     */
    public function setMin($min)
    {
        $this->min = $min;
        return $this;
    }

    /**
     * Get max
     *
     * @return float
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set max
     * @param float $max
     * @return MinMax
     */
    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }

    public function getInRange($value)
    {
        $value_ = $value;
        $value_ = $this->min !== null ? ($value_ && $value_ < $this->min ? $this->min : $value_) : $value_;
        $value_ = $this->max !== null ? ($value_ && $value_ > $this->max ? $this->max : $value_) : $value_;
        return $value_;
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