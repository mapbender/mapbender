<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

/**
 * Description of TileMatrixSetLink
 *
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
     * Sets tileMatrixSet
     * @param string $tileMatrixSet
     * @return \Mapbender\WmtsBundle\Component\TileMatrixSetLink
     */
    public function setTileMatrixSet($tileMatrixSet)
    {
        $this->tileMatrixSet = $tileMatrixSet;
        return $this;
    }

    /**
     * Sets tileMatrixSetLimits.
     * @param integer $tileMatrixSetLimits
     * @return \Mapbender\WmtsBundle\Component\TileMatrixSetLink
     */
    public function setTileMatrixSetLimits($tileMatrixSetLimits)
    {
        $this->tileMatrixSetLimits = $tileMatrixSetLimits;
        return $this;
    }


}
