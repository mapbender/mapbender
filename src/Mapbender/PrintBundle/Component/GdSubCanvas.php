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
        imagefilledrectangle($this->resource, 0, 0, $width, $height, IMG_COLOR_TRANSPARENT);
        $this->offsetX = $offsetX;
        $this->offsetY = $offsetY;
    }

    /**
     * Blends image back onto the parent canvas at the appropriate offset.
     * (see constructor)
     */
    public function mergeBack()
    {
        imagealphablending($this->parent->resource, true);
        imagecopyresampled($this->parent->resource, $this->resource,
            $this->offsetX, $this->offsetY, 0,0,
            $this->getWidth(), $this->getHeight(),
            $this->getWidth(), $this->getHeight());
    }

    /**
     * @return int
     */
    public function getOffsetX()
    {
        return $this->offsetX;
    }

    /**
     * @return int
     */
    public function getOffsetY()
    {
        return $this->offsetY;
    }

    /**
     * @param float[][] $coordinates (in parent canvas pixel space)
     * @param int $color
     */
    public function drawPolygonOutline($coordinates, $color)
    {
        parent::drawPolygonOutline($this->translatePoints($coordinates), $color);
    }

    /**
     * @param float[][] $coordinates (in parent canvas pixel space)
     * @param int $color
     */
    public function drawPolygonBody($coordinates, $color)
    {
        parent::drawPolygonBody($this->translatePoints($coordinates), $color);
    }

    /**
     * @param float[][] $coordinates (in parent canvas pixel space)
     * @param int $color
     */
    public function drawLineString($coordinates, $color)
    {
        parent::drawLineString($this->translatePoints($coordinates), $color);
    }

    /**
     * @param float $centerX in pixel space
     * @param float $centerY in pixel space
     * @param int $color Gdish
     * @param float $diameterX
     * @param float $diameterY
     */
    public function drawFilledEllipse($centerX, $centerY, $color, $diameterX, $diameterY)
    {
        $translatedCenter = $this->translatePoints(array(array($centerX, $centerY)));
        parent::drawFilledEllipse($translatedCenter[0][0], $translatedCenter[0][1], $color, $diameterX, $diameterY);
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
