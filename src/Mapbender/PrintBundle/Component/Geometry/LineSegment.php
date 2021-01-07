<?php

namespace Mapbender\PrintBundle\Component\Geometry;


use Mapbender\PrintBundle\Util\CoordUtil;

class LineSegment
{
    /** @var Point2D */
    protected $from;
    /** @var Point2D */
    protected $to;

    /**
     * @param Point2D $from
     * @param Point2D $to
     */
    public function __construct(Point2D $from, Point2D $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @param float[] $a
     * @return LineSegment
     */
    public static function fromArray(array $a)
    {
        if (count($a) < 2) {
            throw new \InvalidArgumentException("Input array must have at least 2 elements, got " . count($a));
        }
        $a = array_values($a);
        $from = $a[0];
        $to = $a[1];
        if (!$from instanceof Point2D) {
            $from = Point2D::fromArray($from);
        }
        if (!$to instanceof Point2D) {
            $to = Point2D::fromArray($to);
        }
        return new static($from, $to);
    }

    public function toArray()
    {
        return array(
            $this->from->toArray(),
            $this->to->toArray(),
        );
    }

    /**
     * @return Point2D
     */
    public function getStart()
    {
        return $this->from;
    }

    /**
     * @return Point2D
     */
    public function getEnd()
    {
        return $this->to;
    }

    /**
     * @return float
     */
    public function getLength()
    {
        return CoordUtil::distance($this->from->toArray(), $this->to->toArray());
    }

    /**
     * @param float $ratio in [0;1] for a point on the LineSegment (other values produce points OUTSIDE the LineSegment)
     * @return Point2D
     */
    public function getBisectingPoint($ratio)
    {
        $xYArray = CoordUtil::interpolateLinear($this->from->toArray(), $this->to->toArray(), $ratio);
        return Point2D::fromArray($xYArray);
    }

    /**
     * @param float $offset in [0;length] for a point on the LineSegment
     * @return Point2D
     */
    public function getPointAtLenghtOffset($offset)
    {
        $ratio = $offset / $this->getLength();
        return $this->getBisectingPoint($ratio);
    }

    /**
     * Generate a new LineSegment using offset points on this LineSegment as start and end.
     *
     * @param float $offsetFrom in [0;length] for start points on this LineSegment
     * @param float $length in [-$offsetFrom;+$offsetFrom] for end points on this LineSegment
     * @return LineSegment
     */
    public function getSlice($offsetFrom, $length)
    {
        $length0 = $this->getLength();
        $ratioFrom = $offsetFrom / $length0;
        $ratioTo = ($offsetFrom + $length) / $length0;
        return new static($this->getBisectingPoint($ratioFrom), $this->getBisectingPoint($ratioTo));
    }
}
