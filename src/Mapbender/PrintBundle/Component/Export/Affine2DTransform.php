<?php


namespace Mapbender\PrintBundle\Component\Export;


/**
 * Transformation implementation for 2D coordinates using a 3x2 matrix.
 *
 * Currently only exposes box-to-box (translate and scale) construction.
 *
 * @todo: Export / print quality might benefit if we offer rotation here
 *        but the current code does not rotate geometry. Features are
 *        rotated after rasterization, along with the other raster layers.
 */
class Affine2DTransform
{
    /**
     * @var float[][] 3x2 matrix
     */
    protected $rows;

    /**
     * @param float[][] $matrixRows
     */
    protected function __construct($matrixRows)
    {
        $this->rows = $matrixRows;
    }

    /**
     * Construct planar box-to-box scaling and translation transformation.
     * @param Box $from
     * @param Box $to
     * @return Affine2DTransform
     */
    public static function boxToBox(Box $from, Box $to)
    {
        return new static(array(
            array(
                $to->getWidth() / $from->getWidth(),
                0.0,
                ($to->left - $from->left * $to->getWidth() / $from->getWidth()),
            ),
            array(
                0.0,
                $to->getHeight() / $from->getHeight(),
                ($to->bottom - $from->bottom * $to->getHeight() / $from->getHeight()),
            ),
        ));
    }

    /**
     * @param float[] $pair numerically indexed; x at index 0, y at index 1
     * @return float[] numerically indexed, same as input (x at index 0, y at index 1)
     */
    public function transformPair(array $pair)
    {
        // straight 2d vector x matrix
        return array(
            $pair[0] * $this->rows[0][0] + $pair[1] * $this->rows[0][1] + 1.0 * $this->rows[0][2],
            $pair[0] * $this->rows[1][0] + $pair[1] * $this->rows[1][1] + 1.0 * $this->rows[1][2],
        );
    }

    /**
     * @param float[] $p with entries 'x' and 'y'
     * @return float[] 2 entries 'x' and 'y'
     */
    public function transformXy(array $p)
    {
        // straight 2d vector x matrix
        return array(
            'x' => $p['x'] * $this->rows[0][0] + $p['y'] * $this->rows[0][1] + 1.0 * $this->rows[0][2],
            'y' => $p['x'] * $this->rows[1][0] + $p['y'] * $this->rows[1][1] + 1.0 * $this->rows[1][2],
        );
    }
}

