<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Pdf\ImageBridge;
use Mapbender\PrintBundle\Component\Region\FullPage;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;

/**
 * Handles rendering Legends in Print.
 *
 * Because Legends can appear both on the main page and following pages, and can introduce any number of
 * page breaks, this is currently not feasible to implement as a plugin, but warrants its own, special-purpose
 * API.
 *
 * Rewire 'mapbender.print.legend_handler.service' to displace this implementation with your own.
 */
class LegendHandler
{
    /** @var ImageTransport */
    protected $imageTransport;
    /** @var string */
    protected $resourceDir;
    /** @var ImageBridge */
    protected $imageBridge;

    /**
     * @param ImageTransport $imageTransport
     * @param string $resourceDir
     * @param string $tempDir
     */
    public function __construct(ImageTransport $imageTransport, $resourceDir, $tempDir)
    {
        $this->imageTransport = $imageTransport;
        $this->resourceDir = $resourceDir;
        $this->imageBridge = new ImageBridge($tempDir, 'mb_print_legend');
    }

    /**
     * Prepares LegendBlock objects for later rendering (potentially across first page + following pages)
     *
     * Override this if you need to generate legends dynamically, or using methods other than a presupplied URL.
     *
     * @param array $printJobData
     * @return LegendBlock[]
     */
    public function collectLegends($printJobData)
    {
        if (empty($printJobData['legends'])) {
            return array();
        }
        $blocks = array();
        foreach ($printJobData['legends'] as $groupIndex => $groupData) {
            foreach ($groupData as $title => $url) {
                $block = $this->prepareUrlBlock($title, $url);
                if ($block) {
                    $blocks[] = $block;
                }
            };
        }
        return $blocks;
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf $pdf
     * @param TemplateRegion $region
     * @param LegendBlock[] $blocks
     * @param bool $allowPageBreaks
     * @param Template|array $templateData
     * @param array $jobData
     */
    public function addLegends($pdf, $region, $blocks, $allowPageBreaks, $templateData, $jobData)
    {
        $margins = $this->getMargins($region);
        $x = $margins['x'];
        $y = $margins['y'];

        foreach ($blocks as $block) {
            if ($block->isRendered()) {
                continue;
            }
            $imageMmWidth = PrintService::dotsToMm($block->getWidth(), 96);
            $imageMmHeight = PrintService::dotsToMm($block->getHeight(), 96);
            // allot a little extra height for the title text
            // @todo: this should scale with font size
            // @todo: support multi-line text
            $blockHeightMm = round($imageMmHeight + 10);

            if ($y != $margins['y'] && $y + $blockHeightMm > $region->getHeight()) {
                // spill to next column
                $x += 105;
                $y = $margins['y'];
            }
            if ($x + 20 > $region->getWidth()) {
                if (!$allowPageBreaks) {
                    return;
                }
                // we need a page break
                $this->addPage($pdf, $templateData, $jobData);
                $region = FullPage::fromCurrentPdfPage($pdf);
                $margins = $this->getMargins($region);
                $x = $margins['x'];
                $y = $margins['y'];
            }

            $pageX = $x + $region->getOffsetX();
            $pageY = $y + $region->getOffsetY();
            $pdf->SetXY($pageX, $pageY);
            $pdf->Cell(0,0,  utf8_decode($block->getTitle()));
            $this->imageBridge->addImageToPdf($pdf, $block->resource,
                $pageX,
                $pageY + 5,
                $imageMmWidth, $imageMmHeight);
            $block->setIsRendered(true);

            $y += $blockHeightMm + $margins['y'];
        }
    }


    /**
     * Adds a new page to the PDF to render more legends. Also implicitly adds watermarks, if defined in the
     * template and job.
     *
     * @param PDF_Extensions|\FPDF $pdf $pdf
     * @param Template|array $templateData
     * @param array $jobData
     */
    public function addPage($pdf, $templateData, $jobData)
    {
        // @todo: support something other than hardcoded A4 size in portrait orientation
        $pdf->addPage('P', 'a4');
        $this->addLegendPageImage($pdf, $templateData, $jobData);
        // @todo: make hard-coded spill page legend title font configurable
        $pdf->SetFont('Arial', 'B', 11);
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param Template|array $templateData
     * @param array $jobData
     */
    protected function addLegendPageImage($pdf, $templateData, $jobData)
    {
        if (empty($templateData['legendpage_image']) || empty($jobData['legendpage_image'])) {
            return;
        }
        $sourcePath = $this->resourceDir . '/' . $jobData['legendpage_image']['path'];
        $region = $templateData['legendpage_image'];
        if (file_exists($sourcePath)) {
            $this->imageBridge->addImageToPdf($pdf, $sourcePath, $region['x'], $region['y'], 0, $region['height']);
        } else {
            $defaultPath = $this->resourceDir . '/images/legendpage_image.png';
            if ($defaultPath !== $sourcePath && file_exists($defaultPath)) {
                $this->imageBridge->addImageToPdf($pdf, $defaultPath, $region['x'], $region['y'], 0, $region['height']);
            }
        }
    }

    /**
     * @param string $title
     * @param string $url
     * @return LegendBlock|null
     */
    public function prepareUrlBlock($title, $url)
    {
        $image = $this->imageTransport->downloadImage($url);
        if ($image) {
            return new LegendBlock($image, $title);
        } else {
            return null;
        }
    }

    /**
     * Returns the desired outer margin around the rendered legends
     *
     * @param TemplateRegion $region
     * @return int[] with keys 'x' and 'y', values in mm
     */
    protected function getMargins($region)
    {
        // @todo: config values please
        if ($region instanceof FullPage) {
            return array(
                'x' => 5,
                'y' => 10,
            );
        } else {
            return array(
                'x' => 5,
                'y' => 5,
            );
        }
    }
}
