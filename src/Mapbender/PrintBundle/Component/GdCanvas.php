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
}
