<?php


namespace Mapbender\PrintBundle\Component;

/**
 * Incrementally extending, coordinate system agnostic feature bounds tracking.
 * min / max x / y keep updating when adding more points.
 * This allows interchangeable use for simple geometries as well as multi-geometries.
 */
class FeatureBounds
{
    /** @var (float|null)[] */
    protected $minima;
    /** @var (float|null)[] */
    protected $maxima;

    /**
     * @param float[][] $points in any coordinate system
     */
    public function __construct($points = array())
    {
        $this->minima = array(null, null);
        $this->maxima = array(null, null);
        $this->addPoints($points);
    }

    /**
     * @param float[][] $points in any coordinate system
     */
    public function addPoints($points)
    {
        foreach ($points as $point) {
            // don't know if point is numerically indexed or has 'x' / 'y' keys
            // => normalize
            $normPoint = array_values($point);
            for ($i = 0; $i < 2; ++$i) {
                if ($this->minima[$i] === null || $normPoint[$i] < $this->minima[$i]) {
                    $this->minima[$i] = $normPoint[$i];
                }
                if ($this->maxima[$i] === null || $normPoint[$i] > $this->maxima[$i]) {
                    $this->maxima[$i] = $normPoint[$i];
                }
            }
        }
    }

    /**
     * @return float|null
     */
    public function getMinX()
    {
        return $this->minima[0];
    }

    /**
     * @return float|null
     */
    public function getMinY()
    {
        return $this->minima[1];
    }

    /**
     * @return float|null
     */
    public function getMaxX()
    {
        return $this->maxima[0];
    }

    /**
     * @return float|null
     */
    public function getMaxY()
    {
        return $this->maxima[1];
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->minima[1] === null;
    }

    /**
     * @return float|null
     */
    public function getWidth()
    {
        return $this->isEmpty() ? null : ($this->getMaxX() - $this->getMinX());
    }

    /**
     * @return float|null
     */
    public function getHeight()
    {
        return $this->isEmpty() ? null : ($this->getMaxY() - $this->getMinY());
    }

}
