<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Region\FontStyle;

/**
 * A rectangular portion of the PDF we want to generate.
 * Has dimensions, margins, and some font styling.
 * If the template parser didn't give us specific font styles,
 * we still carry some defaults.
 */
class TemplateRegion implements \ArrayAccess
{
    /** @var float */
    protected $width;
    /** @var float */
    protected $height;
    /** @var float[] */
    protected $offsets;
    /** @var FontStyle|null */
    protected $style;

    /**
     * @param float $width in mm
     * @param float $height in mm
     * @param float[] $offsets x/y of upper left corner in mm (default: 0;0)
     */
    public function __construct($width, $height, $offsets = null)
    {
        $this->width = floatval($width);
        $this->height = floatval($height);
        if ($offsets) {
            $this->offsets = array_map('floatval', array_values(array_slice($offsets, 0, 2)));
        } else {
            $this->offsets = array(0, 0);
        }
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return FontStyle|null
     */
    public function getFontStyle()
    {
        return $this->style;
    }

    /**
     * @param FontStyle $style
     */
    public function setFontStyle(FontStyle $style)
    {
        $this->style = $style;
    }

    /**
     * @return float in mm
     */
    public function getOffsetX()
    {
        return $this->offsets[0];
    }

    /**
     * @return float in mm
     */
    public function getOffsetY()
    {
        return $this->offsets[1];
    }


    // array-style access support
    public function offsetGet($offset)
    {
        switch($offset) {
            case 'x':
                return $this->offsets[0];
            case 'y':
                return $this->offsets[1];
            case 'width':
                return $this->width;
            case 'height':
                return $this->height;
            case 'font':
                return $this->style->getFontName();
            case 'fontsize':
                return $this->style->getSize();
            case 'color':
                return $this->style->getColor();
            default:
                throw new \RuntimeException("Invalid offset " . print_r($offset, true));
        }
    }

    public function offsetExists($offset)
    {
        switch ($offset) {
            case 'x':
            case 'y':
            case 'width':
            case 'height':
                return true;
            case 'font':
            case 'fontsize':
            case 'color':
                return !!$this->style;
            default:
                return false;
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException(get_class($this) . " does not support array-style mutation");
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException(get_class($this) . " does not support array-style mutation");
    }
}
