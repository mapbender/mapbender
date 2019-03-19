<?php


namespace Mapbender\WmtsBundle\Component\Export;


use Mapbender\PrintBundle\Component\Export\Box;

class TileMatrixTms extends TileMatrix
{
    protected $extentBottom;

    protected $baseUrl;

    public function __construct($baseUrl, $resolution, $identifier, $origin, $tileWidth, $tileHeight)
    {
        parent::__construct($resolution, $identifier, $origin[0], $tileWidth, $tileHeight);
        $this->rowSign = 1;
        $this->baseUrl = $baseUrl;
        $this->extentBottom = $origin[1];
    }

    public function getTileUrl($tileX, $tileY)
    {
        return rtrim($this->baseUrl, '/') . "/{$this->identifier}/{$tileX}/{$tileY}.png";
    }

    /**
     * @param Box $extent
     * @return ImageTile[]
     */
    public function getTileRequests(Box $extent)
    {
        $tilesOut = array();
        $unitsPerTile = $this->getUnitsPerTile();
        $fy0 = ($extent->top - $this->extentBottom - $unitsPerTile['y']) / $unitsPerTile['y'];
        $fx0 = ($extent->left - $this->extentLeft) / $unitsPerTile['x'];
        $tx0 = intval(floor($fx0));
        $px0 = intval(round(($tx0 - $fx0) * $this->tileWidth));
        $ty0 = intval(ceil($fy0));
        $py0 = intval(round(($fy0 - $ty0) * $this->tileHeight));

        for ($ty = $ty0; ;) {
            $tileExtentTop = ($ty + 1) * $unitsPerTile['y'] + $this->extentBottom;
            $tileExtentBottom = $ty * $unitsPerTile['y'] + $this->extentBottom;
            for ($tx = $tx0; ; ++$tx) {
                $offsetX = $px0 + $this->tileWidth * ($tx - $tx0);
                $offsetY = $py0 + $this->tileHeight * abs($ty - $ty0);
                $tilesOut[] = new ImageTile($tx, $ty, $offsetX, $offsetY);

                if ($this->extentLeft + $unitsPerTile['x'] * $tx >= $extent->right) {
                    break;
                }
            }
            if ($tileExtentBottom <= $extent->bottom) {
                break;
            }
            $ty -= 1;
        }

        return $tilesOut;
    }
}
