<?php


namespace Mapbender\PrintBundle\Component\Export;


class WmsGrid
{
    /** @var WmsTile[] */
    protected $tiles = array();
    /** @var int */
    protected $width = 0;
    /** @var int */
    protected $height = 0;


    /**
     * Return pixel-space width
     * @return int
     */
    public function getWidth()
    {
        return intval($this->width);
    }

    /**
     * Return pixel-space height
     * @return int
     */
    public function getHeight()
    {
        return intval($this->height);
    }

    /**
     * @param WmsTile $tile
     */
    public function addTile(WmsTile $tile)
    {
        $offsetBox = $tile->getOffsetBox();
        $boxWidth = abs($offsetBox->getWidth());
        $boxHeight = abs($offsetBox->getHeight());
        if (intval($boxWidth) != $boxWidth) {
            throw new \RuntimeException("Offset box has non-integral width " . print_r($boxWidth, true));
        }
        if (intval($boxHeight) != $boxHeight) {
            throw new \RuntimeException("Offset box has non-integral height " . print_r($boxHeight, true));
        }
        $tileX1 = $offsetBox->left + intval($boxWidth);
        $tileY1 = $offsetBox->bottom + intval($boxHeight);
        $this->width = max($this->width, $tileX1);
        $this->height = max($this->height, $tileY1);
        $this->tiles[] = $tile;
    }

    /**
     * @return WmsTile[]
     */
    public function getTiles()
    {
        return $this->tiles;
    }
}
