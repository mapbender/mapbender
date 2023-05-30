<?php

namespace Mapbender\WmtsBundle\Component;

/**
 * @author Paul Schmidt
 */
class TileMatrixSetLink
{
    /**
     * Reference to a tileMatrixSet
     * @var string
     */
    public $tileMatrixSet;

    /**
     * Indices limits for this tileMatrixSet. The absence of this element means that tile row and tile col
     * indices are only limited by 0 and the corresponding tileMatrixSet maximum definitions.
     * @var
     */
    public $tileMatrixSetLimits;

    /**
     * Returns tileMatrixSet
     * @return string
     */
    public function getTileMatrixSet()
    {
        return $this->tileMatrixSet;
    }

    /**
     * Returns tileMatrixSetLimits
     * @return integer
     */
    public function getTileMatrixSetLimits()
    {
        return $this->tileMatrixSetLimits;
    }

    /**
     * @param string $tileMatrixSet
     * @return TileMatrixSetLink
     */
    public function setTileMatrixSet($tileMatrixSet)
    {
        $this->tileMatrixSet = $tileMatrixSet;
        return $this;
    }

    /**
     * @param integer $tileMatrixSetLimits
     * @return TileMatrixSetLink
     */
    public function setTileMatrixSetLimits($tileMatrixSetLimits)
    {
        $this->tileMatrixSetLimits = $tileMatrixSetLimits;
        return $this;
    }
}
