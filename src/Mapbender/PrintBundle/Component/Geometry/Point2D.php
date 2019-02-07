<?php

namespace Mapbender\PrintBundle\Component\Geometry;


class Point2D implements \ArrayAccess
{
    /** @var float */
    protected $x;
    /** @var float */
    protected $y;

    /**
     * @param float $x
     * @param float $y
     */
    public function __construct($x, $y)
    {
        $this->x = floatval($x);
        $this->y = floatval($y);
    }

    public function offsetExists($offset)
    {
        return $offset == 0 || $offset == 'x';
    }

    public function offsetGet($offset)
    {
        switch ($offset) {
            case '0':
            case 'x':
                return $this->x;
            case 1:
            case 'y':
                return $this->y;
            default:
                throw new \RuntimeException("No such offset " . print_r($offset, true));
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException("Immutable after construction");
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException("Immutable after construction");
    }

    /**
     * @param float[] $a
     * @return Point2D
     */
    public static function fromArray(array $a)
    {
        if (array_key_exists('x', $a) && array_key_exists('y', $a)) {
            return new static($a['x'], $a['y']);
        } else {
            return new static($a[0], $a[1]);
        }
    }

    /**
     * @return float[]
     */
    public function toArray()
    {
        return array(
            $this->x,
            $this->y,
        );
    }
}
