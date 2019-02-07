<?php

namespace Mapbender\PrintBundle\Component\Geometry;


class Point2D extends Tuple
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
        parent::__construct(array(
            $x,
            $y,
        ));
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
}
