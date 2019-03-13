<?php


namespace Mapbender\WmtsBundle\Component\Export;


class LayerRendererWmts extends LayerRendererTiled
{
    /**
     * @param $layerDef
     * @param $resolution
     * @return TileMatrix
     */
    protected function getTileMatrix($layerDef, $resolution)
    {
        $matrixDef = $layerDef['matrix'];
        return new TileMatrixWmts($layerDef['url'], $layerDef['resolution'],
            $matrixDef['identifier'], $matrixDef['topLeftCorner'],
            $matrixDef['tileWidth'], $matrixDef['tileHeight']);
    }
}
