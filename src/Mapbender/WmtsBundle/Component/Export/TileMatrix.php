<?php


namespace Mapbender\WmtsBundle\Component\Export;


use Mapbender\PrintBundle\Component\Export\Box;

abstract class TileMatrix
{
    protected $rowSign;
    protected float $resolution;
    protected int $tileWidth;
    protected int $tileHeight;
    protected string $identifier;

    /**
     * @param float $extentLeft
     */
    public function __construct($resolution, $identifier, protected $extentLeft, $tileWidth, $tileHeight)
    {
        $this->resolution = floatval($resolution);
        $this->tileWidth = intval($tileWidth);
        $this->tileHeight = intval($tileHeight);
        $this->identifier = strval($identifier);
    }

    /**
     * @param Box $extent
     * @return ImageTile[]
     */
    abstract public function getTileRequests(Box $extent);

    /**
     * @return float
     */
    public function getResolution()
    {
        return $this->resolution;
    }

    protected function getUnitsPerTile()
    {
        return [
            'x' => $this->resolution * $this->tileWidth,
            'y' => $this->resolution * $this->tileHeight,
        ];
    }

    abstract public function getTileUrl($tileX, $tileY);

    /**
     * @return int
     */
    public function getTileWidth()
    {
        return $this->tileWidth;
    }

    /**
     * @return int
     */
    public function getTileHeight()
    {
        return $this->tileHeight;
    }
}
