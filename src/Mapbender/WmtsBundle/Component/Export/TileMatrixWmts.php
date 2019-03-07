<?php


namespace Mapbender\WmtsBundle\Component\Export;


use Mapbender\PrintBundle\Component\Export\Box;

class TileMatrixWmts extends TileMatrix
{
    /** @var string */
    protected $urlTemplate;
    /** @var float */
    protected $extentTop;

    public function __construct($urlTemplate, $resolution, $identifier, $origin, $tileWidth, $tileHeight)
    {
        parent::__construct($resolution, $identifier, $origin[0], $tileWidth, $tileHeight);
        $this->extentTop = $origin[1];
        $this->urlTemplate = $urlTemplate;
    }

    /**
     * @param int $tileX
     * @param int $tileY
     * @return string
     */
    public function getTileUrl($tileX, $tileY)
    {
        // @todo: styles support
        return strtr($this->urlTemplate, array(
            '{TileMatrix}' => $this->identifier,
            '{TileCol}' => $tileX,
            '{TileRow}' => $tileY,
        ));
    }

    /**
     * @param Box $extent
     * @return ImageTile[]
     */
    public function getTileRequests(Box $extent)
    {
        $tilesOut = array();
        $unitsPerTile = $this->getUnitsPerTile();
        $fy0 = -($extent->top - $this->extentTop) / $unitsPerTile['y'];
        $fx0 = ($extent->left - $this->extentLeft) / $unitsPerTile['x'];
        $tx0 = intval(floor($fx0));
        $px0 = intval(round(($tx0 - $fx0) * $this->tileWidth));
        $ty0 = intval(floor($fy0));
        $py0 = intval(round(($ty0 - $fy0) * $this->tileHeight));

        for ($ty = $ty0; ;) {
            $tileExtentTop = $this->extentTop - $ty * $unitsPerTile['y'];
            $tileExtentBottom = $this->extentTop - ($ty + 1) * $unitsPerTile['y'];
            for ($tx = $tx0; ; ++$tx) {
                $offsetX = $px0 + $this->tileWidth * ($tx - $tx0);
                $offsetY = $py0 + $this->tileHeight * ($ty - $ty0);
                $tilesOut[] = new ImageTile($tx, $ty, $offsetX, $offsetY);

                if ($this->extentLeft + $unitsPerTile['x'] * $tx >= $extent->right) {
                    break;
                }
            }
            if ($tileExtentBottom <= $extent->bottom) {
                break;
            }
            $ty += 1;
        }

        return $tilesOut;
    }
}
