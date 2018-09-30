<?php


namespace Mapbender\PrintBundle\Component\Export;

/**
 * Axis-aligned 2D box.
 */
class Box
{
    /** @var float */
    public $left;
    /** @var float */
    public $bottom;
    /** @var float */
    public $right;
    /** @var float */
    public $top;

    /**
     * @param int|float $left
     * @param int|float $bottom
     * @param int|float $right
     * @param int|float $top
     */
    public function __construct($left, $bottom, $right, $top)
    {
        $this->left = floatval($left);
        $this->bottom = floatval($bottom);
        $this->right = floatval($right);
        $this->top = floatval($top);
    }

    /**
     * @param float $centerX
     * @param float $centerY
     * @param float $width
     * @param float $height
     * @return Box
     */
    public static function fromCenterAndSize($centerX, $centerY, $width, $height)
    {
        $left = $centerX - 0.5 * $width;
        $bottom = $centerY - 0.5 * $height;
        return new static($left, $bottom, $left + $width, $bottom + $height);
    }

    /**
     * Return a new, bigger box that can fit this box if it were rotated by $degrees degrees around its center.
     * NOTE: all Box instances are axis aligned. Box rotation is hypothetical.
     *
     * @param $degrees
     * @return Box
     */
    public function getExpandedForRotation($degrees)
    {
        $sine = abs(sin(deg2rad($degrees)));
        $cosine = abs(cos(deg2rad($degrees)));
        $widthScale = $cosine + $sine * $this->getHeight() / $this->getWidth();
        $heightScale = $cosine + $sine * $this->getWidth() / $this->getHeight();
        return $this->getScaled($widthScale, $heightScale);
    }

    /**
     * Return a new box scaled by $widthFactor and $heightFactor, maintaining current center.
     *
     * @param float $widthFactor
     * @param float $heightFactor
     * @return Box
     */
    public function getScaled($widthFactor, $heightFactor)
    {
        $center = array(
            ($this->right - $this->left) * 0.5 + $this->left,
            ($this->top - $this->bottom) * 0.5 + $this->bottom,
        );
        $newWidth = $widthFactor * $this->getWidth();
        $newHeight = $heightFactor * $this->getHeight();
        return $this->fromCenterAndSize($center[0], $center[1], $newWidth, $newHeight);
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->right - $this->left;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->top - $this->bottom;
    }
}
