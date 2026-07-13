<?php


namespace Mapbender\PrintBundle\Component\Export;

class WmsGridOptions
{
    protected array $maxGetMapDimensions;

    /**
     * @param int[] $maxGetMapDimensions
     * @param int[] $tileBuffer
     */
    public function __construct($maxGetMapDimensions, protected $tileBuffer)
    {
        $this->maxGetMapDimensions = array_values($maxGetMapDimensions);
    }

    /**
     * @return int
     */
    public function getUnbufferedWidth(): float|int
    {
        return $this->maxGetMapDimensions[0] - 2 * $this->getBufferHorizontal();
    }

    /**
     * @return int
     */
    public function getUnbufferedHeight(): float|int
    {
        return $this->maxGetMapDimensions[1] - 2 * $this->getBufferVertical();
    }

    /**
     * @return int
     */
    public function getBufferHorizontal()
    {
        return $this->tileBuffer[0];
    }

    /**
     * @return int
     */
    public function getBufferVertical()
    {
        return $this->tileBuffer[1];
    }

}
