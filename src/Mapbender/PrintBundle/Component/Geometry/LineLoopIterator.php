<?php


namespace Mapbender\PrintBundle\Component\Geometry;


class LineLoopIterator extends LineStringIterator
{
    public function __construct(array $points)
    {
        if (count($points) < 2) {
            throw new \InvalidArgumentException("Cannot form a loop from " . count($points) . " points");
        }
        parent::__construct($points);
    }

    public function valid()
    {
        // unline parent, allow iteration to continue even if lineEndIterator is one past the last point
        return $this->lineStartIterator->valid();
    }

    public function current()
    {
        if ($this->lineEndIterator->valid()) {
            return parent::current();
        } else {
            // Connect final point back to first point
            return new LineSegment($this->lineStartIterator->current(), $this->points[0]);
        }
    }
}
