<?php

namespace Mapbender\WmsBundle\Component;

/**
 * MinMax class.
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class MinMax
{

    /**
     * ORM\Column(type="float", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $min;

    /**
     * ORM\Column(type="float", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $max;

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

}

?>
