<?php


namespace Mapbender\PrintBundle\Component\Export;

/**
 * 2D resolution (scale factors for pixel => projected space
 */
class Resolution
{
    /**
     * @param float $horizontal
     * @param float $vertical
     */
    public function __construct(protected $horizontal, protected $vertical)
    {
    }

    /**
     * @return float
     */
    public function getHorizontal()
    {
        return $this->horizontal;
    }

    /**
     * @return float
     */
    public function getVertical()
    {
        return $this->vertical;
    }
}
