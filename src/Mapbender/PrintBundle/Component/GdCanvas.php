<?php


namespace Mapbender\PrintBundle\Component;


class GdCanvas
{
    /** @var resource Gdish */
    public $resource;
    /** @var int */
    public $width;
    /** @var int */
    public $height;

    public function __construct($width, $height)
    {
        $width = intval(round($width));
        $height = intval(round($height));
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException("Invalid width / height " . print_r(array($width, $height), true));
        }
        $this->resource = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($this->resource, 255, 255, 255);
        imagefilledrectangle($this->resource, 0, 0, $width, $height, $bg);
        imagecolordeallocate($this->resource, $bg);
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param float[][] $coordinates (pixel space)
     * @param int $color
     */
    public function drawPolygonOutline($coordinates, $color)
    {
        $pointsFlat = call_user_func_array('array_merge', $coordinates);
        imagepolygon($this->resource, $pointsFlat, count($coordinates), $color);
    }

    /**
     * @param float[][] $coordinates (pixel space)
     * @param int $color
     */
    public function drawPolygonBody($coordinates, $color)
    {
        $pointsFlat = call_user_func_array('array_merge', $coordinates);
        imagesetthickness($this->resource, 0);
        imagefilledpolygon($this->resource, $pointsFlat, count($coordinates), $color);
    }

    /**
     * @param float[][] $coordinates (pixel space)
     * @param int $color
     */
    public function drawLineString($coordinates, $color)
    {
        if (PHP_VERSION_ID >= 70200 && count($coordinates) > 2) {
            $pointsFlat = call_user_func_array('array_merge', $coordinates);
            imageopenpolygon($this->resource, $pointsFlat, count($coordinates), $color);
        } else {
            $from = $coordinates[0];
            foreach (array_slice($coordinates, 1) as $to) {
                imageline($this->resource, $from[0], $from[1], $to[0], $to[1], $color);
                $from = $to;
            }
        }
    }
}
