<?php


namespace Mapbender\PrintBundle\Component\Export;


use Mapbender\PrintBundle\Component\GdCanvas;

class ExportCanvas extends GdCanvas
{
    /** @var FeatureTransform */
    public $featureTransform;

    /**
     * @param number $width
     * @param number $height
     * @param FeatureTransform $featureTransform
     */
    public function __construct($width, $height, $featureTransform)
    {
        parent::__construct($width, $height);
        $this->featureTransform = $featureTransform;
    }
}
