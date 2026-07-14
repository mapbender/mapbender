<?php


namespace Mapbender\PrintBundle\Component\Export;


class FeatureTransform extends Affine2DTransform
{
    /**
     * @param float $lineScale
     */
    protected function __construct(array $matrixRows, public $lineScale = 1.0)
    {
        parent::__construct($matrixRows);
    }

    /**
     * @param Box $from
     * @param Box $to
     * @param float $lineScale
     * @return FeatureTransform
     */
    public static function boxToBox(Box $from, Box $to, $lineScale = 1.0): static
    {
        // PHPStorm doesn't believe it, but it's true: parent returns static
        /** @var static $transform */
        $transform = parent::boxToBox($from, $to);
        $transform->lineScale = $lineScale;
        return $transform;
    }
}
