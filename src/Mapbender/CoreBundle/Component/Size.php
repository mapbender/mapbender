<?php
namespace Mapbender\CoreBundle\Component;

/**
 * @author Paul Schmidt
 */
class Size
{
    public $width = 0;

    public $height = 0;

    /**
     * 
     * @param integer $width Width
     * @param integer $height Height
     */
    public function __construct($width = null, $height = null)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param int $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $height
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Returns a Size as an array
     * 
     * @return array
     */
    public function toArray()
    {
        return array("width" => $this->width, "height" => $this->height);
    }

}
