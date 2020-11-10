<?php


namespace Mapbender\PrintBundle\Component\Geometry;

/**
 * Loops over a number of points and yields LineSegment instances constructed
 * from adjacent pairs of these points.
 * "LineString" in this case refers to the non-closed the nature of the yielded LineSegments.
 * The last point is not connected back to the first by a LineSegment.
 */
class LineStringIterator implements \Iterator
{
    /** @var Point2D[] */
    protected $points;

    /** @var \ArrayIterator */
    protected $lineStartIterator;
    /** @var \ArrayIterator */
    protected $lineEndIterator;

    /**
     * @param array $points elements should be Point2D instances or arrays that can be promoted to Point2D
     */
    public function __construct(array $points)
    {
        if (count($points) === 1) {
            throw new \InvalidArgumentException("Cannot form lines from exactly 1 point");
        }
        $this->points = array();
        foreach ($points as $point) {
            if ($point instanceof Point2D) {
                $this->points[] = $point;
            } else {
                $this->points[] = Point2D::fromArray($point);
            }
        }
        $this->lineStartIterator = new \ArrayIterator($this->points);
        $this->lineEndIterator = new \ArrayIterator($this->points);
    }

    public function rewind()
    {
        $this->lineStartIterator->rewind();
        $this->lineEndIterator->rewind();
        $this->lineEndIterator->next();
    }

    public function next()
    {
        $this->lineStartIterator->next();
        $this->lineEndIterator->next();
    }

    public function valid()
    {
        return $this->lineStartIterator->valid() && $this->lineEndIterator->valid();
    }

    /**
     * @return LineSegment
     */
    public function current()
    {
        return new LineSegment($this->lineStartIterator->current(), $this->lineEndIterator->current());
    }

    public function key()
    {
        return $this->lineStartIterator->current();
    }
}

