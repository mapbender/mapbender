<?php


namespace Mapbender\PrintBundle\Component;

/**
 * Cutout region of a GdCanvas that can be used for temporary compositing.
 * This is particularly useful for layered alpha-blended surfaces that
 * need to be stamped out precisely with alpha blending turned off, while
 * still storing opacity properly.
 */
class GdSubCanvas extends GdCanvas
{
    /** @var GdCanvas */
    protected $parent;

    /** @var int */
    protected $offsetX;
    /** @var int */
    protected $offsetY;

    public function __construct(GdCanvas $parent, $offsetX, $offsetY, $width, $height)
    {
        $this->parent = $parent;
        parent::__construct($width, $height);
        imagesavealpha($this->resource, true);
        imagealphablending($this->resource, false);
        imagefilledrectangle($this->resource, 0, 0, $width, $height, $this->transparent);
        $this->offsetX = $offsetX;
        $this->offsetY = $offsetY;
    }

    /**
     * @return int GDish representation for fully white but also fully transparent color
     */
    public function getTransparent()
    {
        return $this->transparent;
    }

    public function mergeBack()
    {
        imagealphablending($this->parent->resource, true);
        imagecopyresampled($this->parent->resource, $this->resource,
            $this->offsetX, $this->offsetY, 0,0,
            $this->getWidth(), $this->getHeight(),
            $this->getWidth(), $this->getHeight());
    }

    /**
     * Translate given (pixel space) coordinate pairs to local subspace by subtracting x/y offsets
     *
     * @param float[][] $points
     * @return float[][]
     */
    protected function translatePoints($points)
    {
        $pointsOut = array();
        foreach ($points as $pair) {
            // we don't know if $pair contains numeric or 'x' / 'y' keys
            // => normalize to numeric
            $pairNumeric = array_values($pair);
            $pointsOut[] = array(
                $pairNumeric[0] - $this->offsetX,
                $pairNumeric[1] - $this->offsetY,
            );
        }
        return $pointsOut;
    }
}
