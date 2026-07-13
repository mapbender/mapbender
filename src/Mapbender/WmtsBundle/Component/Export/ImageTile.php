<?php


namespace Mapbender\WmtsBundle\Component\Export;


class ImageTile
{
    /**
     * @param int $tileX in TileMatrix space
     * @param int $tileY in TileMatrix space
     * @param int $offsetX in pixel space
     * @param int $offsetY in pixel space (GD convention: 0 is top)
     */
    public function __construct(protected $tileX, protected $tileY, protected $offsetX, protected $offsetY)
    {
    }

    /**
     * @return int
     */
    public function getTileX()
    {
        return $this->tileX;
    }

    /**
     * @return int
     */
    public function getTileY()
    {
        return $this->tileY;
    }

    /**
     * @return int
     */
    public function getOffsetX()
    {
        return $this->offsetX;
    }

    /**
     * @return int
     */
    public function getOffsetY()
    {
        return $this->offsetY;
    }

}
