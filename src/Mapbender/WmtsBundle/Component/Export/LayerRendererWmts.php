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
        $delta = INF;
        $matrixIdentifier = null;
        $closestResolution = null;
        foreach ($layerDef['matrixResolutions'] as $resolutionInfo) {
            $matrixResolution = $resolutionInfo['resolution'];
            $matrixDelta = abs($matrixResolution - $resolution);
            if ($matrixDelta < $delta) {
                $delta = $matrixDelta;
                $closestResolution = $matrixResolution;
                $matrixIdentifier = $resolutionInfo['identifier'];
            }
        }
        foreach ($layerDef['matrixset']['tilematrices'] as $tileMatrix) {
            if ($tileMatrix['identifier'] == $matrixIdentifier) {
                return new TileMatrixWmts($layerDef['url'], $closestResolution, $tileMatrix['identifier'], $tileMatrix['topLeftCorner'], $tileMatrix['tileWidth'], $tileMatrix['tileHeight']);
            }
        }
        throw new \LogicException("Cannot find tile matrix for resolution {$resolution}");
    }
}
