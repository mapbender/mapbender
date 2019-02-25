<?php


namespace Mapbender\PrintBundle\Component\Pdf;


use Mapbender\PrintBundle\Component\PDF_Extensions;
use Mapbender\PrintBundle\Component\TemplateRegion;

/**
 * Hides / helps work around the file-only limitation of FPDF library when it comes to adding images
 * to a PDF. We internally work with GD resources, which must be dumped to file before they
 * can become part of a PDF, but we generally don't want to bother with this in other parts of the code.
 *
 * Having this standalone removes the need for (potentially circular) injection / passing of the PrintService into
 * other related components.
 *
 * Currently used by both PrintService and LegendHandler (separate instances, effectively same temp dir
 * because they share the smae configuration parameter value).
 *
 * @todo: add (MultiCell) text height calculations; see e.g.: https://stackoverflow.com/a/54533457
 */
class PdfUtil
{
    const DEFAULT_PREFIX = 'mb_pdf_build';

    /** @var string */
    protected $tempDir;
    /** @var string */
    protected $tempFilePrefix;

    /**
     * @param string $tempDir
     * @param string|null $tempFilePrefix
     */
    public function __construct($tempDir, $tempFilePrefix = null)
    {
        $this->tempDir = $tempDir ?: sys_get_temp_dir();
        $this->tempFilePrefix = $tempFilePrefix ?: self::DEFAULT_PREFIX;
    }

    /**
     * Puts an image onto the current page of given $pdf at specified offset (in mm units).
     *
     * @param PDF_Extensions|\FPDF $pdf
     * @param resource|string $gdResOrPath
     * @param int $xOffset in mm
     * @param int $yOffset in mm
     * @param int $width optional, to rescale image
     * @param int $height optional, to rescale image
     */
    public function addImageToPdf($pdf, $gdResOrPath, $xOffset, $yOffset, $width=0, $height=0)
    {
        if (is_resource($gdResOrPath)) {
            // FPDF library can embed files, but not gd resources
            $imageName = $this->makeTempFile();
            imagepng($gdResOrPath, $imageName);
            $this->addImageToPdf($pdf, $imageName, $xOffset, $yOffset, $width, $height);
            unlink($imageName);
        } else {
            $pdf->Image($gdResOrPath, $xOffset, $yOffset, $width, $height, 'png', '', false, 0);
        }
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param resource|string $gdResOrPath
     * @param TemplateRegion $region
     */
    public function addImageToPdfRegion($pdf, $gdResOrPath, $region)
    {
        $this->addImageToPdf($pdf, $gdResOrPath,
            $region->getOffsetX(), $region->getOffsetY(),
            $region->getWidth(), $region->getHeight());
    }

    /**
     * Creates a ~randomly named temp file with preconfigured prefix (plus optional extra $prefix) and returns its name.
     *
     * @param string|null $prefix
     * @return string
     */
    public function makeTempFile($prefix = null)
    {
        $filePath = tempnam($this->tempDir, $this->tempFilePrefix . ($prefix ?: ''));
        // tempnam may return false in undocumented error cases
        if (!$filePath) {
            throw new \RuntimeException("Failed to create temp file with prefix '$prefix' in '{$this->tempDir}'");
        }
        return $filePath;
    }
}
