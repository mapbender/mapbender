<?php


namespace Mapbender\PrintBundle\Component\Geometry;


class Tuple
{
    /** @var float[] */
    protected $components;

    /**
     * @param float[] $components
     */
    public function __construct($components)
    {
        if (!count($components)) {
            throw new \InvalidArgumentException("Tuple cannot be 0-dimensional");
        }
        $this->components = array_map('\floatval', $components);
    }

    /**
     * @param int $index
     * @return float
     */
    public function getComponent($index)
    {
        return $this->components[$index];
    }

    /**
     * @return int
     */
    public function getDimensionality()
    {
        return count($this->components);
    }

    /**
     * @return float[]
     */
    public function toArray()
    {
        return $this->components;
    }
}
