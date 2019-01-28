<?php


namespace Mapbender\PrintBundle\Component\Export;

class WmsGridOptions
{
    protected $maxGetMapSize;
    protected $tileBufferHorizontal;
    protected $tileBufferVertical;

    /**
     * @param int $maxGetMapSize
     * @param int $tileBufferHorizontal
     * @param int $tileBufferVertical
     */
    public function __construct($maxGetMapSize, $tileBufferHorizontal, $tileBufferVertical)
    {
        $this->maxGetMapSize = $maxGetMapSize;
        $this->tileBufferHorizontal = $tileBufferHorizontal;
        $this->tileBufferVertical = $tileBufferVertical;
    }

    /**
     * @return int
     */
    public function getUnbufferedHeight()
    {
        return $this->maxGetMapSize - 2 * $this->getBufferVertical();
    }

    /**
     * @return int
     */
    public function getUnbufferedWidth()
    {
        return $this->maxGetMapSize - 2 * $this->getBufferHorizontal();
    }

    /**
     * @return int
     */
    public function getBufferVertical()
    {
        return $this->tileBufferVertical;
    }

    /**
     * @return int
     */
    public function getBufferHorizontal()
    {
        return $this->tileBufferHorizontal;
    }
}
