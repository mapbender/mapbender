<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;

/**
 * TileMatrix class describes a particular tile matrix.
 * @author Paul Schmidt
 */
class TileMatrix implements MutableUrlTarget
{
    /**
     * Tile matrix identifier. Typically an abreviation of the ScaleDenominator value or its equivalent pixel size
     * @var string identifier
     */
    public $identifier;

    /**
     * Scale denominator level of this tile matrix
     * @var float scaledenominator
     */
    public $scaledenominator;

    /**
     * Href for TMS TileSet
     * @var string href
     */
    public $href;

    /**
     * Position in CRS coordinates of the top-left corner of this tile matrix. This are the  precise coordinates
     *  of the top left corner of top left pixel of the 0,0 tile in SupportedCRS coordinates of this TileMatrixSet.
     * @var float[]
     */
    public $topleftcorner;

    /**
     * Width of each tile of this tile matrix in pixels.
     * @var integer tilewidth
     */
    public $tilewidth;

    /**
     * Height of each tile of this tile matrix in pixels
     * @var integer
     */
    public $tileheight;

    /**
     * Width of the matrix (number of tiles in width)
     * @var integer
     */
    public $matrixwidth;

    /**
     * Height of the matrix (number of tiles in height)
     * @var integer
     */
    public $matrixheight;

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $value
     */
    public function setIdentifier($value)
    {
        $this->identifier = $value;
    }

    /**
     * @return float
     */
    public function getScaledenominator()
    {
        return $this->scaledenominator;
    }

    /**
     * @param float $value
     */
    public function setScaledenominator($value)
    {
        $this->scaledenominator = floatval($value);
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param string $href
     */
    public function setHref($href)
    {
        $this->href = $href;
    }


    /**
     * @return float[]
     */
    public function getTopleftcorner()
    {
        return $this->topleftcorner;
    }

    /**
     * @param float[] $value
     */
    public function setTopleftcorner($value)
    {
        $this->topleftcorner = $value;
    }

    /**
     * @return string
     */
    public function getTilewidth()
    {
        return $this->tilewidth;
    }

    /**
     * @param string $value
     */
    public function setTilewidth($value)
    {
        $this->tilewidth = intval($value);
    }

    /**
     * @return string
     */
    public function getTileheight()
    {
        return $this->tileheight;
    }

    /**
     * @param string $value
     */
    public function setTileheight($value)
    {
        $this->tileheight = intval($value);
    }

    /**
     * @return string
     */
    public function getMatrixwidth()
    {
        return $this->matrixwidth;
    }

    /**
     * @param string $value
     */
    public function setMatrixwidth($value)
    {
        $this->matrixwidth = intval($value);
    }

    /**
     * @return string
     */
    public function getMatrixheight()
    {
        return $this->matrixheight;
    }

    /**
     * @param string $value
     */
    public function setMatrixheight($value)
    {
        $this->matrixheight = intval($value);
    }

    public function mutateUrls(OneWayTransformer $transformer)
    {
        if ($url = $this->getHref()) {
            $this->setHref($transformer->process($url));
        }
    }
}
