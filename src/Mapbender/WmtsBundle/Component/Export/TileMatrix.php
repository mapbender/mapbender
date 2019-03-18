<?php


namespace Mapbender\WmtsBundle\Component\Export;


use Mapbender\PrintBundle\Component\Export\Box;

abstract class TileMatrix
{
    protected $rowSign;
    /** @var float */
    protected $resolution;
    /** @var float */
    protected $extentLeft;
    /** @var int */
    protected $tileWidth;
    /** @var int */
    protected $tileHeight;
    /** @var string */
    protected $identifier;

    public function __construct($resolution, $identifier, $extentLeft, $tileWidth, $tileHeight)
    {
        $this->resolution = floatval($resolution);
        $this->extentLeft = $extentLeft;
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
        return array(
            'x' => $this->resolution * $this->tileWidth,
            'y' => $this->resolution * $this->tileHeight,
        );
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
