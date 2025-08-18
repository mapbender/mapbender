<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Legend\LegendBlock;
use Mapbender\PrintBundle\Component\Legend\LegendBlockContainer;
use Mapbender\PrintBundle\Component\Legend\LegendBlockGroup;
use Mapbender\PrintBundle\Component\Pdf\PdfUtil;
use Mapbender\PrintBundle\Component\Region\FontStyle;
use Mapbender\PrintBundle\Component\Region\FullPage;
use Mapbender\PrintBundle\Component\Region\NullRegion;
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
 * @todo: allow out-of-order rendering of legends or legend groups, if it reduces total space
 * @todo: (optionally) suppress legend repetitions, based on ~equal url; careful with assigned title...
 */
class LegendHandler
{
    protected PdfUtil $pdfUtil;
    protected float $maxColumnWidthMm = 100.;
    protected float $maxImageDpi = 96.;
    protected string $legendPageFontName = 'Arial';
    /**
     * @var bool if true, the columns are rendered only as wide as they need to be. If false, all columns are $maxColumnWidthMm wide
     */
    protected bool $dynamicColumnSizes = false;

    public function __construct(
        protected ImageTransport $imageTransport,
        protected string         $resourceDir,
        ?string                  $tempDir,
        protected string         $canvasLegendClass,
    )
    {
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
            $block = match ($data['type']) {
                'url' => $this->prepareUrlBlock($data['layerName'], $data['url'], $printJobData, $data["isDynamic"] ?? false),
                'style', 'canvas' => $this->prepareStyleBlock($data),
                default => null,
            };
            if ($block) {
                $group->addBlock($block);
            }
        };
        return $group;
    }

    /**
     * @param PDF_Extensions $pdf $pdf
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
        $currentColumnMaxWidth = 0;
        $pdf->SetFont($this->getLegendPageFont(), 'B', $titleFontSize);

        foreach ($blockGroups as $group) {
            foreach ($group->getBlocks() as $block) {
                if ($block->isRendered()) {
                    continue;
                }
                $measures = $this->measureLegendBlock($block, $region, $pageMargins, $margins, $pdf, $titleFontSize);

                if ($y != $margins['y'] && $y + $measures['height'] > $region->getHeight()) {
                    // spill to next column
                    $x += $this->dynamicColumnSizes
                        ? $currentColumnMaxWidth + 2 * $pageMargins['x']
                        : $this->maxColumnWidthMm + $pageMargins['x'];
                    $y = $pageMargins['y'];
                    $currentColumnMaxWidth = $measures['width'];
                }
                if ($x + $measures['width'] > $region->getWidth() || $region instanceof NullRegion) {
                    if (!$allowPageBreaks) {
                        return;
                    }
                    // we need a page break. Reset all region-specific metrics and remeasure current legend entry
                    $this->addPage($pdf, $templateData, $jobData);
                    $region = FullPage::fromCurrentPdfPage($pdf);
                    $margins = $this->getMargins($region);
                    $x = $pageMargins['x'];
                    $y = $pageMargins['y'];
                    $titleFontSize = $this->getLegendTitleFontSize($region, true);
                    $currentColumnMaxWidth = 0;
                    $measures = $this->measureLegendBlock($block, $region, $pageMargins, $margins, $pdf, $titleFontSize);
                }

                $currentColumnMaxWidth = max($measures['width'], $currentColumnMaxWidth);

                // print title text
                $pdf->SetXY($x + $region->getOffsetX(), $y + $region->getOffsetY());
                $text = mb_convert_encoding($block->getTitle(), 'ISO-8859-1', 'UTF-8');
                // add a fraction of a mm because otherwise floating point arithmetic may cause an unnecessary line break
                $pdf->MultiCell($measures['width'] + 0.00001, $measures['lineHeight'], $text, 0, 'L');

                // print image
                $this->pdfUtil->addImageToPdf($pdf, $block->resource,
                    $x + $region->getOffsetX(),
                    $y + $region->getOffsetY() + $measures['titleHeight'],
                    $measures['imageWidth'], $measures['imageHeight']);
                $block->setIsRendered(true);

                $y += $measures['height'] + $margins['y'];
            }
        }
    }


    /**
     * Adds a new page to the PDF to render more legends. Also implicitly adds watermarks, if defined in the
     * template and job.
     */
    public function addPage(PDF_Extensions|\FPDF $pdf, Template|array $templateData, array $jobData): void
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

    protected function addLegendPageImage(PDF_Extensions|\FPDF $pdf, Template|array $templateData, array $jobData): void
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

    public function prepareStyleBlock(array $legendInfo): ?LegendBlock
    {
        /** @var CanvasLegend $canvasLegend */
        $canvasLegend = new $this->canvasLegendClass($legendInfo["layers"]);
        $image = $canvasLegend->getImage();
        return $image ? new LegendBlock($image, $legendInfo['layerName']) : null;
    }

    public function prepareUrlBlock(string $title, string $url, array $jobData, bool $addDynamicUrlParams = false): ?LegendBlock
    {
        if ($addDynamicUrlParams)  {
            $additionalParams = $this->prepareDynamicUrlParams($jobData);
            $dynamicUrl = UrlUtil::validateUrl($url, $additionalParams);
        }
        $image = $this->imageTransport->downloadImage($addDynamicUrlParams ? $dynamicUrl : $url);
        return $image ? new LegendBlock($image, $title) : null;
    }

    /**
     * Returns the desired outer margins and the distance between layer title and layer graphic around the rendered legends
     *
     * @return int[] with keys 'x', 'y' and 'title_to_image', values in mm
     */
    protected function getMargins(TemplateRegion $region): array
    {
        // @todo: config values please
        return array(
            'x' => 5,
            'y' => $region instanceof FullPage ? 5 : 10,
            'title_to_image' => 0,
        );
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

    public function getLegendPageFont(): string
    {
        return $this->legendPageFontName;
    }

    protected function measureLegendBlock(
        LegendBlock          $block,
        TemplateRegion       $region,
        array                $pageMargins,
        array                $margins,
        PDF_Extensions|\FPDF $pdf,
        float                $titleFontSize,
    ): array
    {
        $imageMmWidth = PrintService::dotsToMm($block->getWidth(), $this->maxImageDpi);
        $imageMmHeight = PrintService::dotsToMm($block->getHeight(), $this->maxImageDpi);

        // calculate maximum available space
        $maxColumnWidth = min($this->maxColumnWidthMm, $region->getWidth()) - 2 * $pageMargins['x'];
        $maxColumnHeight = $region->getHeight() - 2 * $pageMargins['y'];

        // calculate space for the title text
        $text = mb_convert_encoding($block->getTitle(), 'ISO-8859-1', 'UTF-8');
        $nLines = $pdf->getMultiCellTextHeight($text, $maxColumnWidth);
        $textWidth = $nLines > 1 ? $maxColumnWidth : $pdf->GetStringWidth($text);
        // Font size is in 'pt'. Convert pt to mm for line height.
        // see https://en.wikipedia.org/wiki/Point_(typography)
        $lineHeightMm = $titleFontSize * .353;
        $titleHeightMm = $lineHeightMm * $nLines;
        if (array_key_exists('title_to_image', $margins)) {
            $titleHeightMm += $margins['title_to_image'];
        }

        // calculate scale factor for image
        // limit to column width and container height, keep aspect ratio when shrinking
        if ($imageMmWidth > $maxColumnWidth) {
            $scaleFactor = $maxColumnWidth / $imageMmWidth;
        } else {
            $scaleFactor = 1;
        }
        if ($imageMmHeight > $maxColumnHeight - $titleHeightMm) {
            $scaleFactor = min($scaleFactor, ($maxColumnHeight - $titleHeightMm) / $imageMmHeight);
        }

        $scaledImageWidth = $imageMmWidth * $scaleFactor;
        $scaledImageHeight = $imageMmHeight * $scaleFactor;

        return [
            "width" => max($textWidth, $scaledImageWidth),
            "height" => round($scaledImageHeight + $titleHeightMm),
            "imageWidth" => $scaledImageWidth,
            "imageHeight" => $scaledImageHeight,
            "lineHeight" => $lineHeightMm,
            "titleHeight" => $titleHeightMm,
        ];
    }

    private function prepareDynamicUrlParams(array $jobData)
    {
        if (empty($jobData['srs'])) return [];
        $srs = $jobData['srs'];
        $bbox = $this->getJobExtent($jobData);
        return [
            "CRS" => $srs,
            "BBOX" => implode(',', array(
                $bbox->left,
                $bbox->bottom,
                $bbox->right,
                $bbox->top,
            )),
        ];
    }

    protected function getJobExtent($jobData)
    {
        $ext = $jobData['extent'];
        $cnt = $jobData['center'];
        return Box::fromCenterAndSize($cnt['x'], $cnt['y'], $ext['width'], $ext['height']);
    }
}
