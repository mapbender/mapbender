<?php


namespace Mapbender\PrintBundle\Component\Export;

class WmsGridOptions
{
    protected $maxGetMapDimensions;
    protected $tileBuffer;

    /**
     * @param int[] $maxGetMapDimensions
     * @param int[] $tileBuffer
     */
    public function __construct($maxGetMapDimensions, $tileBuffer)
    {
        $this->maxGetMapDimensions = array_values($maxGetMapDimensions);
        $this->tileBuffer = $tileBuffer;
    }

    /**
     * @return int
     */
    public function getUnbufferedWidth()
    {
        return $this->maxGetMapDimensions[0] - 2 * $this->getBufferHorizontal();
    }

    /**
     * @return int
     */
    public function getUnbufferedHeight()
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
