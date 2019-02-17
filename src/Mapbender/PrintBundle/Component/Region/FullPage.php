<?php


namespace Mapbender\PrintBundle\Component\Region;


use Mapbender\PrintBundle\Component\PDF_Extensions;
use Mapbender\PrintBundle\Component\TemplateRegion;

class FullPage extends TemplateRegion
{
    /**
     * @param float $width in PDF units (mm)
     * @param float $height in PDF units (mm)
     */
    public function __construct($width, $height)
    {
        parent::__construct($width, $height, null);
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf $pdf
     * @return static
     */
    public static function fromCurrentPdfPage($pdf)
    {
        return new static($pdf->GetPageWidth(), $pdf->GetPageHeight());
    }
}
