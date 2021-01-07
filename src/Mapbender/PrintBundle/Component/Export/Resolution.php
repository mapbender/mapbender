<?php


namespace Mapbender\PrintBundle\Component\Export;

/**
 * 2D resolution (scale factors for pixel => projected space
 */
class Resolution
{
    /** @var float */
    protected $horizontal;
    /** @var float */
    protected $vertical;

    /**
     * @param float $horizontal
     * @param float $vertical
     */
    public function __construct($horizontal, $vertical)
    {
        $this->horizontal = $horizontal;
        $this->vertical = $vertical;
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
