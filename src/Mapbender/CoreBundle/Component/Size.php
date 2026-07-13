<?php
namespace Mapbender\CoreBundle\Component;

/**
 * @author Paul Schmidt
 */
class Size
{
    /**
     * 
     * @param integer $width Width
     * @param integer $height Height
     */
    public function __construct(public $width = null, public $height = null)
    {
    }

    /**
     * @param int $width
     * @return $this
     */
    public function setWidth($width): static
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
    public function setHeight($height): static
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
    public function toArray(): array
    {
        return ["width" => $this->width, "height" => $this->height];
    }

}
