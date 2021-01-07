<?php

namespace Mapbender\WmsBundle\Component;

/**
 * @author Paul Schmidt
 */
class MinMax
{
    /** @var float|null */
    public $min;
    /** @var float|null */
    public $max;

    /**
     *
     * @param float|null $min
     * @param float|null $max
     */
    public function __construct($min = null, $max = null)
    {
        $this->setMin($min);
        $this->setMax($max);
    }

    /**
     * Get min
     *
     * @return float|null
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
     *
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
}
