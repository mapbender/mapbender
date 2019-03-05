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
        $delta = INF;
        $matrixIdentifier = null;
        $closestResolution = null;
        $closestMatrix = null;
        foreach ($layerDef['matrixset']['tilematrices'] as $tileMatrixDef) {
            $matrixResolution = $tileMatrixDef['scaleDenominator'];
            $matrixDelta = abs($matrixResolution - $resolution);
            if ($matrixDelta < $delta) {
                $delta = $matrixDelta;
                $closestResolution = $matrixResolution;
                $closestMatrix = $tileMatrixDef;
            }
        }
        if ($closestMatrix) {
            // this is NOT the top left corner, but actually the bottom left corner
            // @todo: clean up tms configuration item naming to reflect semantics
            $origin = $closestMatrix['topLeftCorner'];
            return new TileMatrixTms($layerDef['url'], $closestResolution, $closestMatrix['identifier'],
                $origin, $closestMatrix['tileWidth'], $closestMatrix['tileHeight']);
        }
        throw new \LogicException("Cannot find tile matrix for resolution {$resolution}");
    }
}
