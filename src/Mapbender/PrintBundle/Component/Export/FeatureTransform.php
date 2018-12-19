<?php


namespace Mapbender\PrintBundle\Component\Export;


class FeatureTransform extends Affine2DTransform
{
    /** @var float */
    public $lineScale;

    protected function __construct(array $matrixRows, $lineScale = 1.0)
    {
        parent::__construct($matrixRows);
        $this->lineScale = $lineScale;
    }

    /**
     * @param Box $from
     * @param Box $to
     * @param float $lineScale
     * @return FeatureTransform
     */
    public static function boxToBox(Box $from, Box $to, $lineScale = 1.0)
    {
        // PHPStorm doesn't believe it, but it's true: parent returns static
        /** @var static $transform */
        $transform = parent::boxToBox($from, $to);
        $transform->lineScale = $lineScale;
        return $transform;
    }
}
