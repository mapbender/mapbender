<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Legend\LegendBlock;
use Mapbender\PrintBundle\Component\Legend\LegendBlockContainer;
use Mapbender\PrintBundle\Component\Legend\LegendBlockGroup;
use Mapbender\PrintBundle\Component\Pdf\PdfUtil;
use Mapbender\PrintBundle\Component\Region\FontStyle;
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
 *
 * @todo: calculate fit to region for individual blocks and whole groups
 * @todo: add option to keep groups together if they fit
 * @todo: add configuration knob for column width (now: hardcoded to 100mm, because of also hard-coded A4 spill page size)
 * @todo: support line breaks in titles (will impact region fit calculations)
 * @todo: allow out-of-order rendering of legends or legend groups, if it reduces total space
 * @todo: (optionally) suppress legend repetitions, based on ~equal url; careful with assigned title...
 */
class LegendHandler
{
    /** @var ImageTransport */
    protected $imageTransport;
    /** @var string */
    protected $resourceDir;
    /** @var PdfUtil */
    protected $pdfUtil;
    /** @var float */
    protected $maxColumnWidthMm = 100.;
    /** @var float */
    protected $maxImageDpi = 96.;
    /** @var string */
    protected $legendPageFontName = 'Arial';

    /**
     * @param ImageTransport $imageTransport
     * @param string $resourceDir
     * @param string $tempDir
     */
    public function __construct(ImageTransport $imageTransport, $resourceDir, $tempDir)
    {
        $this->imageTransport = $imageTransport;
        $this->resourceDir = $resourceDir;
        $this->pdfUtil = new PdfUtil($tempDir, 'mb_print_legend');
    }

    /**
     * Prepares LegendBlock objects for later rendering (potentially across first page + following pages)
     *
     * Override this if you need to generate legends dynamically, or using methods other than a presupplied URL.
     *
     * @param array $printJobData
     * @return LegendBlockContainer[]
     */
    public function collectLegends($printJobData)
    {
        if (empty($printJobData['legends'])) {
            return array();
        }
        $groups = array();
        foreach ($printJobData['legends'] as $groupData) {
            $group = $this->collectLegendGroup($groupData, $printJobData);

            if (count($group->getBlocks())) {
                $groups[] = $group;
            }
        }
        return $groups;
    }

    /**
     * Should prepare a LegendBlockContainer for a group of legends. In the default implementation,
     * this is a collection of all legends (title + image) for all active layers from a single
     * source service.
     *
     * NOTE: it's legal to return a single LegendBlock here (the interfaces are compatible).
     *
     * @param array $groupData
     * @param array $printJobData if you need to look into the whole thing again...
     * @return LegendBlockContainer
     */
    public function collectLegendGroup($groupData, $printJobData)
    {
        $group = new LegendBlockGroup();
        foreach ($groupData as $key => $data) {
            if (is_array($data)) {
                $url = $data['url'];
                $title = $data['layerName'];
            } else {
                $url = $data;
                $title = $key;
            }
            $block = $this->prepareUrlBlock($title, $url);
            if ($block) {
                $group->addBlock($block);
            }
        };
        return $group;
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf $pdf
     * @param TemplateRegion $region
     * @param LegendBlockContainer[] $blockGroups
     * @param bool $allowPageBreaks
     * @param Template|array $templateData
     * @param array $jobData
     */
    public function addLegends($pdf, $region, $blockGroups, $allowPageBreaks, $templateData, $jobData)
    {
        $margins = $this->getMargins($region);
        $pageMargins = $this->getPageMargins($region);
        $x = $pageMargins['x'];
        $y = $pageMargins['y'];
        $titleFontSize = $this->getLegendTitleFontSize($region);

        foreach ($blockGroups as $group) {
            foreach ($group->getBlocks() as $block) {
                if ($block->isRendered()) {
                    continue;
                }
                $imageMmWidth = PrintService::dotsToMm($block->getWidth(), $this->maxImageDpi);
                $imageMmHeight = PrintService::dotsToMm($block->getHeight(), $this->maxImageDpi);
                // limit to column width, keep aspect ratio when shrinking
                if ($imageMmWidth > $this->maxColumnWidthMm) {
                    $scaleFactor = $this->maxColumnWidthMm / $imageMmWidth;
                } else {
                    $scaleFactor = 1;
                }
                $scaledImageWidth = $imageMmWidth * $scaleFactor;
                $scaledImageHeight = $imageMmHeight * $scaleFactor;

                // allot a little extra height for the title text
                // @todo: support multi-line text
                $blockHeightMm = round($scaledImageHeight + $titleFontSize);

                if ($y != $margins['y'] && $y + $blockHeightMm > $region->getHeight()) {
                    // spill to next column
                    $x += $this->maxColumnWidthMm + $pageMargins['x'];
                    $y = $pageMargins['y'];
                }
                if ($x + 20 > $region->getWidth()) {
                    if (!$allowPageBreaks) {
                        return;
                    }
                    // we need a page break
                    $this->addPage($pdf, $templateData, $jobData);
                    $region = FullPage::fromCurrentPdfPage($pdf);
                    $margins = $this->getMargins($region);
                    $x = $pageMargins['x'];
                    $y = $pageMargins['y'];
                    $titleFontSize = $this->getLegendTitleFontSize($region, true);
                }

                $pageX = $x + $region->getOffsetX();
                $pageY = $y + $region->getOffsetY();
                $pdf->SetXY($pageX, $pageY);
                $text = mb_convert_encoding($block->getTitle(), 'ISO-8859-1', 'UTF-8');
                $nLines = $pdf->getMultiCellTextHeight($text, $this->maxColumnWidthMm);
                // Font size is in 'pt'. Convert pt to mm for line height.
                // see https://en.wikipedia.org/wiki/Point_(typography)
                $lineHeightMm = $titleFontSize * .353;
                $blockTitleHeightMm = $lineHeightMm * $nLines;
                $pdf->MultiCell($this->maxColumnWidthMm, $lineHeightMm, $text, 0, 'L');
                $this->pdfUtil->addImageToPdf($pdf, $block->resource,
                    $pageX,
                    $pageY + $blockTitleHeightMm,
                    $scaledImageWidth, $scaledImageHeight);
                $block->setIsRendered(true);

                $y += $blockHeightMm + $margins['y'];
            }
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
        if ($templateData['orientation'] == 'portrait') {
            $format = array($templateData['pageSize']['width'], $templateData['pageSize']['height']);
            $orientation = 'P';
        } else {
            $format = array($templateData['pageSize']['height'], $templateData['pageSize']['width']);
            $orientation = 'L';
        }
        $pdf->addPage($orientation, $format);

        $this->addLegendPageImage($pdf, $templateData, $jobData);
        $pdf->SetFont($this->legendPageFontName, 'B', $this->getLegendTitleFontSize(null, true));
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
            $this->pdfUtil->addImageToPdf($pdf, $sourcePath, $region['x'], $region['y'], 0, $region['height']);
        } else {
            $defaultPath = $this->resourceDir . '/images/legendpage_image.png';
            if ($defaultPath !== $sourcePath && file_exists($defaultPath)) {
                $this->pdfUtil->addImageToPdf($pdf, $defaultPath, $region['x'], $region['y'], 0, $region['height']);
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
     * @return int[] with keys 'x' and 'y', values in mm
     */
    protected function getMargins(TemplateRegion $region): array
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

    protected function getPageMargins(TemplateRegion $region): array
    {
        return $this->getMargins($region);
    }

    public function getLegendTitleFontSize(?TemplateRegion $region = null, bool $extraPage = false): float
    {
        $fontStyle = $region?->getFontStyle() ?: FontStyle::defaultFactory();
        return $fontStyle->getSize();
    }
}
