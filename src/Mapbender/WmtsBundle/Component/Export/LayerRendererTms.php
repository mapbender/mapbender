<?php


namespace Mapbender\WmtsBundle\Component\Export;


class LayerRendererTms extends LayerRendererTiled
{
    /**
     * @param $layerDef
     * @param $resolution
     * @return TileMatrix
     */
    protected function getTileMatrix($layerDef, $resolution)
    {
        $matrixDef = $layerDef['matrix'];
        // this is NOT the top left corner, but actually the bottom left corner
        // @todo: clean up tms configuration item naming to reflect semantics
        $origin = $matrixDef['topLeftCorner'];
        return new TileMatrixTms($layerDef['url'], $layerDef['resolution'],
            $matrixDef['identifier'], $origin,
            $matrixDef['tileWidth'], $matrixDef['tileHeight']);
    }
}
