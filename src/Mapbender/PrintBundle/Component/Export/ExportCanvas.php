<?php


namespace Mapbender\PrintBundle\Component\Export;


use Mapbender\PrintBundle\Component\GdCanvas;

class ExportCanvas extends GdCanvas
{
    /** @var FeatureTransform */
    public $featureTransform;
    /** @var int */
    public $physicalDpi;

    /**
     * @param number $width
     * @param number $height
     * @param FeatureTransform $featureTransform
     * @param int|null $physicalDpi
     */
    public function __construct($width, $height, $featureTransform, $physicalDpi = null)
    {
        parent::__construct($width, $height);
        $this->featureTransform = $featureTransform;
        $this->physicalDpi = intval($physicalDpi ?: 72);
    }
}
