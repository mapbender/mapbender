<?php

namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\FeatureTransform;
use Mapbender\PrintBundle\Component\Legend\LegendBlockContainer;
use Mapbender\PrintBundle\Component\Legend\LegendBlockGroup;
use Mapbender\PrintBundle\Component\Pdf\PdfUtil;
use Mapbender\PrintBundle\Component\Region\NullRegion;
use Mapbender\PrintBundle\Component\Service\PrintPluginHost;
use Mapbender\PrintBundle\Component\Service\PrintServiceInterface;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Merges map image exports and various other regions, steered by a template,
 * into PDFs.
 *
 * Registered in container at mapbender.print.service
 *
 * @author Stefan Winkelmann
 */
class PrintService extends ImageExportService implements PrintServiceInterface
{
    protected PDF_Extensions|\FPDF $pdf;

    protected Template|array $conf;

    protected array $data;

    protected PdfUtil $pdfUtil;

    /**
     * Collected legends from all frames for multiframe printing
     * @var array
     */
    protected array $collectedLegends = [];

    /**
     * @param LayerRenderer[] $layerRenderers
     * @param string|null $tempDir absolute path or emptyish to autodetect via sys_get_temp_dir()
     */
    public function __construct(
        array                     $layerRenderers,
        protected ImageTransport  $imageTransport,
        protected LegendHandler   $legendHandler,
        protected OdgParser       $templateParser,
        protected PrintPluginHost $pluginHost,
        TypeDirectoryService      $typeDirectoryService,
        LoggerInterface           $logger,
        private readonly ApplicationResolver $applicationResolver,
        protected string          $resourceDir,
        ?string                   $tempDir,
    )
    {
        $this->pdfUtil = new PdfUtil($tempDir, 'mb_print');
        parent::__construct($layerRenderers, $logger, $typeDirectoryService);
    }

    /**
     * Executes the job (plain array), returns a binary string representation of the resulting PDF.
     *
     * @param mixed[] $jobData
     * @return string
     * @throws \Exception on invalid template
     */
    public function doPrint($jobData)
    {
        if (isset($jobData['application']) && !$jobData['application'] instanceof Application) {
            $jobData['application'] = $this->applicationResolver->getApplicationEntityUnsecure($jobData['application']);
        }

        $templateData = $this->getTemplateData($jobData);
        $this->setup($templateData, $jobData);

        $mapImageName = $this->createMapImage($templateData, $jobData);

        $pdf = $this->buildPdf($mapImageName, $templateData, $jobData);

        return $this->dumpPdf($pdf);
    }

    /**
     * Executes the job (plain array), returns a binary string representation of the resulting PDF.
     * Supports both single frame and multiframe print jobs.
     *
     * @param array $jobData
     * @return string
     * @throws \Exception on invalid template
     */
    public function dumpPrint(array $jobData)
    {
        // Handle multiframe structure: {frames: [...], multiFrame: true, ...}
        if (isset($jobData['multiFrame']) && $jobData['multiFrame'] === true) {
            if (!isset($jobData['frames']) || !is_array($jobData['frames'])) {
                throw new RuntimeException("Invalid multiframe structure: missing 'frames' array");
            }
            return $this->doMultiFramePrint($jobData['frames']);
        } else {
            return $this->doPrint($jobData);
        }
    }

    /**
     * Executes the job (plain array), writes the PDF result as directly as possible to $fileName.
     *
     * @param array $jobData
     * @param string $fileName
     * @throws \Exception on invalid template
     */
    public function storePrint(array $jobData, $fileName)
    {
        // NOTE: FPDI's 'direct' file output mode isn't any more efficient than its string output mode
        //       (uses the same amount of memory). This may be more worthwhile with a different PDF lib...
        if (!file_put_contents($fileName, $this->dumpPrint($jobData))) {
            throw new \RuntimeException("Failed to store printout at {$fileName}");
        }
    }

    /**
     * @param array $jobData
     * @return Template|array
     */
    protected function getTemplateData($jobData)
    {
        return $this->templateParser->getConf($jobData['template']);
    }

    /**
     * @param Template|array $templateData
     * @param array $jobData
     */
    protected function setup($templateData, $jobData)
    {
        // @todo: eliminate instance variable $this->data
        $this->data = $jobData;
        // @todo: eliminate instance variable $this->conf
        $this->conf = $templateData;
    }

    protected function getTargetBox($templateData, $jobData)
    {
        $targetWidth = round($templateData['map']['width'] / 25.4 * $jobData['quality']);
        $targetHeight = round($templateData['map']['height'] / 25.4 * $jobData['quality']);
        // NOTE: gd pixel coords are top down
        return new Box(0, $targetHeight, $targetWidth, 0);
    }

    protected function getJobExtent($jobData)
    {
        $box = parent::getJobExtent($jobData);
        // Print only: extend on rotation
        if (isset($jobData['rotation']) && intval($jobData['rotation'])) {
            $box = $box->getExpandedForRotation(floatval($jobData['rotation']));
        }
        return $box;
    }

    /**
     * @param array $templateData
     * @param array $jobData
     * @return string path to stored image
     */
    protected function createMapImage($templateData, $jobData)
    {
        $targetBox = $this->getTargetBox($templateData, $jobData);
        $exportJob = array_replace($jobData, $targetBox->getAbsWidthAndHeight());
        $mapImage = $this->buildExportImage($exportJob);

        // dump to file system immediately to recoup some memory before building PDF
        /** @var \GdImage $mapImage */
        $mapImageName = $this->makeTempFile('mb_print_final');
        imagepng($mapImage, $mapImageName);
        imagedestroy($mapImage);
        return $mapImageName;
    }

    /**
     * @param Template|array $templateData
     * @param string $templateName
     * @return \FPDF|PDF_Extensions
     * @throws \Exception
     */
    protected function makeBlankPdf($templateData, $templateName)
    {
        /** @var PDF_Extensions|\FPDF $pdf */
        $pdf = new PDF_Extensions();
        $pdfPath = $this->templateParser->getTemplateFilePath($templateName, 'pdf');
        $pdf->setSourceFile($pdfPath);
        $pdf->SetAutoPageBreak(false);
        if ($templateData['orientation'] == 'portrait') {
            $format = array($templateData['pageSize']['width'], $templateData['pageSize']['height']);
            $orientation = 'P';
        } else {
            $format = array($templateData['pageSize']['height'], $templateData['pageSize']['width']);
            $orientation = 'L';
        }
        $pdf->addPage($orientation, $format);
        return $pdf;
    }

    /**
     * @param string $mapImageName
     * @param Template|array $templateData
     * @param array $jobData
     * @return \FPDF|PDF_Extensions
     * @throws \Exception
     */
    protected function buildPdf($mapImageName, $templateData, $jobData)
    {
        // @todo: eliminate instance variable $this->pdf
        $this->pdf = $pdf = $this->makeBlankPdf($templateData, $jobData['template']);
        // PDF_Extensions extends Fpdi, which provides importPage() and useTemplate()
        /** @var \setasign\Fpdi\Fpdi $pdf */
        $tplidx = $pdf->importPage(1);

        $hasTransparentBg = $this->checkPdfBackground($jobData['template']);
        if (!$hasTransparentBg) {
            $pdf->useTemplate($tplidx);
        }
        $this->addMapImage($pdf, $mapImageName, $templateData);
        unlink($mapImageName);

        if ($hasTransparentBg) {
            $pdf->useTemplate($tplidx);
        }

        $this->afterMainMap($pdf, $templateData, $jobData);

        return $pdf;
    }

    /**
     * Returns the binary string representation of the $pdf
     *
     * @param PDF_Extensions|\FPDF $pdf
     * @return string
     */
    protected function dumpPdf($pdf)
    {
        return $pdf->Output(null, 'S');
    }

    /**
     * @param \FPDF|PDF_Extensions $pdf
     * @param string $mapImageName
     * @param Template|array $templateData
     */
    protected function addMapImage($pdf, $mapImageName, $templateData)
    {
        $region = $templateData['map'];
        $this->addImageToPdfRegion($pdf, $mapImageName, $region);
        // add map border (default is black)
        $pdf->Rect($region['x'], $region['y'], $region['width'], $region['height']);
    }

    /**
     * Returns a list of template region names that should be excluded from regular template region
     * processing. If you have multiple main maps, this is the place to extend.
     * @param array $jobData
     * @return string[]
     * @see afterMainMap
     * @see handleRegion
     *
     */
    protected function getFirstPageSpecialRegionNames($jobData)
    {
        return array(
            // Map is already rendered (c.f. method name xD)
            'map',
            // Legend can perform page breaks, which means
            // a) we must separately track which legends have already been rendered on main page, unlike other regions
            // b) rendering the remaining legends introduces page breaks, and as such must wait until all other
            //    main page regions are handled
            'legend',
            // 'legendpage_image' appears as a top-level template region, but is only relevant
            // for spill pages produced during legend rendering (which we also suppress, so...)
            // NOTE: the only real effect of blacklisting it is suppressing a warning in afterMainMap
            'legendpage_image',
        );
    }

    /**
     * Renders the remaining regions on the first page after the main map image has been added.
     * This excludes the legend, because the legend rendering process, if it begins on the first
     * page, may spill over and start adding more pages.
     *
     * @param \FPDF|PDF_Extensions $pdf
     * @param Template $template
     * @param array $jobData
     */
    protected function afterMainMap($pdf, $template, $jobData)
    {
        $this->processTemplateRegionsAndFields($pdf, $template, $jobData);

        $legends = $this->legendHandler->collectLegends($jobData);
        $this->handleMainPageLegends($pdf, $template, $jobData, $legends);
        $this->finishMainPage($pdf, $template, $jobData);
        $this->handleRemainingLegends($pdf, $template, $jobData, $legends);
    }

    /**
     * Process template regions, text fields, and coordinates.
     * Extracted to avoid code duplication between single and batch printing.
     *
     * @param \FPDF|PDF_Extensions $pdf
     * @param Template $template
     * @param array $jobData
     */
    protected function processTemplateRegionsAndFields($pdf, $template, $jobData)
    {
        $regionBlacklist = $this->getFirstPageSpecialRegionNames($jobData);
        foreach ($template->getRegions() as $region) {
            if (!in_array($region->getName(), $regionBlacklist)) {
                if (!$this->handleRegion($pdf, $region, $jobData)) {
                    $this->logger->warning("Unhandled print template region " . print_r($region->getName(), true));
                }
            }
        }

        if (!empty($template['fields'])) {
            $this->addTextFields($pdf, $template, $jobData);
        }

        $this->addCoordinates($pdf, $template, $jobData);
    }

    /**
     * Called after all default regions on the main map page have been populated, including embedded legend regions.
     * Does absolutely nothing by default.
     *
     * Override this to do anything you want to do happen before the legend spill pages start rendering. You MAY
     * also add more pages to the PDF here.
     *
     * @param \FPDF|PDF_Extensions $pdf
     * @param Template $template
     * @param array $jobData
     */
    public function finishMainPage($pdf, $template, $jobData)
    {
        // default implementation: do nothing
    }

    /**
     * Should populate a TemplateRegion on the first page of the PDF being generated.
     * Nothing happening in this method or called by it should add page breaks to the pdf.
     *
     * @param \FPDF|PDF_Extensions $pdf
     * @param TemplateRegion $region
     * @param array $jobData
     * @return bool
     */
    protected function handleRegion($pdf, $region, $jobData)
    {
        switch ($region->getName()) {
            default:
                return false;
            case 'northarrow':
                return $this->addNorthArrow($pdf, $region, $jobData);
            case 'overview':
                return $this->addOverviewMap($pdf, $region, $jobData);
            case 'scalebar':
                return $this->addScaleBar($pdf, $region, $jobData);
            case 'dynamic_image':
                return $this->addDynamicImage($pdf, $region, $jobData);
        }
    }

    /**
     * Should fill any main page regions designated for legend rendering (default: single, optional region named
     * 'legend'). Should not perform page breaks.
     * LegendBlock remembers if it has already been rendered or not, so remaining legend blocks can be rendered
     * onto spill pages later.
     *
     * @param \FPDF|PDF_Extensions $pdf
     * @param Template $template
     * @param array $jobData
     * @param LegendBlockContainer[] $legendBlocks
     */
    protected function handleMainPageLegends($pdf, $template, $jobData, $legendBlocks)
    {
        // @todo: multiple viable main page legend regions?
        $regionNames = array(
            'legend',
        );
        foreach ($regionNames as $legendRegionName) {
            if ($template->hasRegion($legendRegionName)) {
                $region = $template->getRegion($legendRegionName);
                $this->legendHandler->addLegends($pdf, $region, $legendBlocks, false, $template, $jobData);
            }
        }
    }

    /**
     * Renders any legend blocks not yet marked as rendered on extra pages appended to the end of the pdf.
     *
     * @param \FPDF|PDF_Extensions $pdf
     * @param Template $template
     * @param array $jobData
     * @param LegendBlockContainer[] $legendBlocks
     */
    protected function handleRemainingLegends($pdf, $template, $jobData, $legendBlocks)
    {
        // give the LegendHandler a region with zero space, so it will be forced to page-break
        // immediately
        $region = NullRegion::getInstance();
        $this->legendHandler->addLegends($pdf, $region, $legendBlocks, true, $template, $jobData);
    }

    /**
     * Fills textual regions on the first page.
     *
     * @param \FPDF|PDF_Extensions $pdf
     * @param Template|array $template
     * @param array $jobData
     */
    protected function addTextFields($pdf, $template, $jobData)
    {
        foreach ($template->getTextFields() as $region) {
            $fieldName = $region->getName();
            // skip extent fields, see special handling in addCoordinates method
            if (preg_match("/^extent/", $fieldName)) {
                continue;
            }
            $text = $this->getTextFieldContent($fieldName, $jobData);
            if ($text !== null) {
                $lineHeight = $region->getFontStyle()->getLineHeightMm();
                $this->applyFontStyle($pdf, $region);
                $pdf->SetXY($region['x'] - 1, $region['y'] + 0.25 * $lineHeight);
                $encodedText = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
                $pdf->MultiCell($region['width'], $lineHeight, $encodedText, 0, $region->getAlignment());
            }
        }
        // reset text color to default black
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * @param \FPDF|PDF_Extensions $pdf
     * @param TemplateRegion|array $region
     */
    protected function applyFontStyle($pdf, $region)
    {
        list($r, $g, $b) = CSSColorParser::parse($region['color']);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont($region['font'], '', floatval($region['fontsize']));
    }

    /**
     * Should return text to be printed into a 'field' (=textual template region).
     * A null return value completely skips processing of the field.
     *
     * @param string $fieldName
     * @param array $jobData
     * @return string|null
     */
    protected function getTextFieldContent($fieldName, $jobData)
    {
        $pluginText = $this->getPluginHost()->getTextFieldContent($fieldName, $jobData);
        if ($pluginText !== null) {
            return $pluginText;
        }
        switch ($fieldName) {
            case 'date':
                return date('d.m.Y');
            case 'scale':
                return '1 : ' . $jobData['scale_select'];
            case 'user_name';
                return $jobData['userName'];
            case 'dynamic_text':
                if (isset($jobData['dynamic_text']['text'])) {
                    return $jobData['dynamic_text']['text'] ?: null;
                }
                break;
            default:
                if (isset($jobData['extra'][$fieldName])) {
                    return $jobData['extra'][$fieldName];
                } else {
                    // @todo: log warning?
                    return null;
                }
        }
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param TemplateRegion $region
     * @param array $jobData
     * @return bool to indicate success (always true here)
     */
    protected function addNorthArrow($pdf, $region, $jobData)
    {
        $northarrow = $this->resourceDir . '/images/northarrow.png';
        $rotation = intval($jobData['rotation']);

        if ($rotation != 0) {
            $image = imagecreatefrompng($northarrow);
            $transColor = imagecolorallocatealpha($image, 255, 255, 255, 127);
            $rotatedImage = imagerotate($image, $rotation, $transColor);
            $srcSize = array(imagesx($image), imagesy($image));

            $destSize = array(imagesx($rotatedImage), imagesy($rotatedImage));
            $x = intval(abs(($srcSize[0] - $destSize[0]) / 2));
            $y = intval(abs(($srcSize[1] - $destSize[1]) / 2));

            // Avoid actually enlarging the image during crop (added regions would be filled with opaque black)
            // in either dimension
            $cropWidth = min($srcSize[0], $destSize[0] - $x);
            $cropHeight = min($srcSize[1], $destSize[1] - $y);
            $northarrow = $this->cropImage($rotatedImage, $x, $y, $cropWidth, $cropHeight, true);
        }
        $this->addImageToPdfRegion($pdf, $northarrow, $region);
        return true;
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param TemplateRegion $region
     * @param array $jobData
     * @return bool
     */
    protected function addOverviewMap($pdf, $region, $jobData)
    {
        if (empty($jobData['overview'])) {
            return false;
        }
        $ovData = $jobData['overview'];
        $quality = $jobData['quality'];
        // calculate needed image size
        $ovImageWidth = round($region['width'] / 25.4 * $quality);
        $ovImageHeight = round($region['height'] / 25.4 * $quality);
        // gd pixel coords are top down!
        $ovPixelBox = new Box(0, $ovImageHeight, $ovImageWidth, 0);
        $cnt = $ovData['center'];
        $ovWidth = $ovData['height'] * $region['width'] / $region['height'];
        $ovExtent = Box::fromCenterAndSize($cnt['x'], $cnt['y'], $ovWidth, $ovData['height']);
        $image = $this->buildExportImage(array(
            'layers' => $ovData['layers'],
            'width' => $ovImageWidth,
            'height' => $ovImageHeight,
            'extent' => $ovExtent->getAbsWidthAndHeight(),
            'center' => $ovExtent->getCenterXy(),
            'quality' => $quality,
        ));

        $ovTransform = FeatureTransform::boxToBox($ovExtent, $ovPixelBox, 1.0);
        /** @var \GdImage $image */
        $red = imagecolorallocate($image, 255, 0, 0);
        // GD imagepolygon expects a flat, numerically indexed, 1d list of concatenated coordinates,
        // and we have 2D sub-arrays with 'x' and 'y' keys. Convert.
        $flatPoints = call_user_func_array('array_merge', array_map('array_values', array(
            $ovTransform->transformXy($jobData['extent_feature'][0]),
            $ovTransform->transformXy($jobData['extent_feature'][3]),
            $ovTransform->transformXy($jobData['extent_feature'][2]),
            $ovTransform->transformXy($jobData['extent_feature'][1]),
        )));
        imagepolygon($image, $flatPoints, 4, $red);

        $this->addImageToPdfRegion($pdf, $image, $region);
        imagecolordeallocate($image, $red);
        imagedestroy($image);
        // draw border rectangle
        $pdf->Rect($region['x'], $region['y'], $region['width'], $region['height']);
        return true;
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param TemplateRegion $region
     * @param array $jobData
     * @return bool to indicate success (always true here)
     */
    protected function addScaleBar($pdf, $region, $jobData)
    {
        $totalWidth = $region['width'];
        // Quantize bar length to whole scale units
        $sectionWidth = 10;
        $nSections = floor($totalWidth / 10);
        $barWidth = $nSections * $sectionWidth;
        // As per definition of scale, 10mm on the printout measures $scale centimeters in map space
        $totalMeters = 0.01 * $jobData['scale_select'] * $nSections;

        // if the region width isn't evenly divided by 10mm, offset the bar to center it
        $barX0 = $region->getOffsetX() + 0.5 * ($totalWidth - $nSections * $sectionWidth);

        $pdf->SetFont('arial', '', 10);

        $pdf->Text($barX0, $region['y'] - 1, '0');
        $scaleText = "{$totalMeters}m";
        $scaleTextLength = strlen($scaleText);
        // heuristics time: the 'm' takes ~2.75 units, 0 and most other digits ~2 units
        $endTextOffset = $barWidth - 2.75 - 1.975 * ($scaleTextLength - 1);
        $pdf->Text($barX0 + $endTextOffset, $region['y'] - 1, $scaleText);

        $pdf->SetLineWidth(0.1);
        $pdf->SetDrawColor(0, 0, 0);
        for ($i = 0; $i < $nSections; ++$i) {
            if ($i & 1) {
                $pdf->SetFillColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(0, 0, 0);
            }
            $pdf->Rect($barX0 + $i * $sectionWidth, $region['y'], $sectionWidth, 2, 'FD');
        }
        return true;
    }

    /**
     * Special-casing for coordinates text fields, which render
     * to (up to) 4 different template regions, some of which
     * use up / down font directions.
     *
     * @param PDF_Extensions|\FPDF $pdf
     * @param Template $template
     * @param array $jobData
     * @return bool
     */
    protected function addCoordinates($pdf, $template, $jobData)
    {
        if (empty($jobData['extent_feature']) || empty($jobData['extent'])) {
            $this->logger->warning("Skipping coordinates rendering, missing data");
            return false;
        }
        // correction factor and round precision if WGS84
        if ($jobData['extent']['width'] < 1) {
            $offsetXUrY = 3;
            $precision = 6;
        } else {
            $offsetXUrY = 2;
            $precision = 2;
        }

        $efData = $jobData['extent_feature'];
        $fieldDataMapping = array(
            // @todo: clean up magic number offsets; these should depend on font
            //        size, text length and direction
            'extent_ll_x' => array(
                'value' => $efData[0]['x'],
                'offsetX' => 3,
                'offsetY' => 30,
                'direction' => 'U',
            ),
            'extent_ll_y' => array(
                'value' => $efData[0]['y'],
                'offsetX' => 0,
                'offsetY' => 3,
                'direction' => 'R',
            ),
            'extent_ur_x' => array(
                'value' => $efData[2]['x'],
                'offsetX' => 1,
                'offsetY' => 0,
                'direction' => 'D',
            ),
            'extent_ur_y' => array(
                'value' => $efData[2]['y'],
                'offsetX' => $offsetXUrY,
                'offsetY' => 3,
                'direction' => 'R',
            ),
        );
        foreach ($fieldDataMapping as $fieldName => $fieldConfig) {
            if (!$template->hasTextField($fieldName)) {
                continue;
            }
            $field = $template->getTextFields()->getMember($fieldName);
            $formattedValue = round($fieldConfig['value'], $precision);
            $direction = $fieldConfig['direction'];
            $x = $field->getOffsetX() + $fieldConfig['offsetX'];
            $y = $field->getOffsetY() + $fieldConfig['offsetY'];
            $this->applyFontStyle($pdf, $field);
            $pdf->TextWithDirection($x, $y, $formattedValue, $direction);
        }
        return true;
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param TemplateRegion $region
     * @param array $jobData
     * @return bool to indicate success
     */
    protected function addDynamicImage($pdf, $region, $jobData)
    {
        if (empty($jobData['dynamic_image']['path'])) {
            return false;
        }
        $dynImage = $this->resourceDir . '/' . $jobData['dynamic_image']['path'];
        if (file_exists($dynImage)) {
            $pdf->Image($dynImage,
                $region->getOffsetX(),
                $region->getOffsetY(),
                0,
                $region->getHeight(),
                'png');
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $jobData
     * @return float
     */
    protected function getDefaultDpi($jobData)
    {
        if (empty($jobData['mapDpi'])) {
            return 72.0;
        } else {
            return floatval($jobData['mapDpi']);
        }
    }

    /**
     * @param array $jobData
     * @return float
     */
    protected function getLineScale($jobData)
    {
        if (empty($jobData['quality'])) {
            return 1.0;
        } else {
            return floatval($jobData['quality']) / $this->getDefaultDpi($jobData);
        }
    }

    /**
     * @param string $templateName
     * @return bool
     */
    private function checkPdfBackground($templateName)
    {
        $pdfString = file_get_contents($this->templateParser->getTemplateFilePath($templateName, 'pdf'));
        return !str_contains($pdfString, '/Outlines');
    }

    /**
     * @param float $dots
     * @param float $dpi
     * @return float
     */
    public static function dotsToMm($dots, $dpi)
    {
        return $dots * 25.4 / $dpi;
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
    public function addImageToPdf($pdf, $gdResOrPath, $xOffset, $yOffset, $width = 0, $height = 0)
    {
        return $this->pdfUtil->addImageToPdf($pdf, $gdResOrPath, $xOffset, $yOffset, $width, $height);
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param resource|string $gdResOrPath
     * @param TemplateRegion $region
     */
    public function addImageToPdfRegion($pdf, $gdResOrPath, $region)
    {
        return $this->pdfUtil->addImageToPdfRegion($pdf, $gdResOrPath, $region);
    }

    /**
     * @return PrintPluginHost
     */
    protected function getPluginHost()
    {
        return $this->pluginHost;
    }

    /**
     * Creates a ~randomly named temp file with given $prefix and returns its name
     *
     * @param string|null $prefix
     * @return string
     */
    protected function makeTempFile($prefix)
    {
        return $this->pdfUtil->makeTempFile($prefix);
    }

    /**
     * Process multiframe print job
     * 
     * @param array $jobData Array of frame data
     * @return string PDF binary content
     * @throws \Exception
     */
    protected function doMultiFramePrint(array $jobData): string
    {
        $mapImageNames = [];

        // Create all map images and store filesystem paths
        foreach ($jobData as $index => $data) {
            $templateData = $this->getTemplateData($data);
            
            // CRITICAL: Call setup() to initialize template dimensions before creating map image
            // Each template (A4 quer, A4 hoch, etc.) has different dimensions that must be set
            $this->setup($templateData, $data);
            
            $mapImageNames[] = $this->createMapImage($templateData, $data);
        }

        $pdf = null;
        // Build PDFs for each item
        foreach ($jobData as $index => $data) {
            $templateData = $this->getTemplateData($data);
            $this->setup($templateData, $data);
            
            $pdf = $this->buildMultiFramePdf($mapImageNames[$index], $templateData, $data, $pdf);
        }

        $this->afterMainMapMulti($pdf, $this->getTemplateData($jobData[0]), $jobData[0]);

        return $this->dumpPdf($pdf);
    }

    /**
     * Build PDF for a single frame in multiframe print
     * 
     * @param string $mapImageName Path to map image file
     * @param Template|array $template Template data
     * @param array $jobData Job data for this frame
     * @param PDF_Extensions|\FPDF|null $pdf Existing PDF object or null for first frame
     * @return PDF_Extensions|\FPDF PDF object with added page
     * @throws \Exception
     */
    protected function buildMultiFramePdf(string $mapImageName, Template|array $template, array $jobData, PDF_Extensions|\FPDF|null $pdf = null): PDF_Extensions|\FPDF
    {
        if (!$pdf) {
            $pdf = $this->makeBlankPdf($template, $jobData['template']);
        } else {
            if ($template['orientation'] == 'portrait') {
                $format = [$template['pageSize']['width'], $template['pageSize']['height']];
                $orientation = 'P';
            } else {
                $format = [$template['pageSize']['height'], $template['pageSize']['width']];
                $orientation = 'L';
            }
            $pdf->addPage($orientation, $format);
            
            // CRITICAL: Set the source file for this template before importing
            // Each template (A4 quer, A4 hoch, etc.) has its own PDF file
            $pdfPath = $this->templateParser->getTemplateFilePath($jobData['template'], 'pdf');
            $pdf->setSourceFile($pdfPath);
        }
        // PDF_Extensions extends Fpdi, which provides importPage() and useTemplate()
        /** @var \setasign\Fpdi\Fpdi $pdf */
        $tplidx = $pdf->importPage(1);
        $pdf->useTemplate($tplidx);

        $this->addMapImage($pdf, $mapImageName, $template);
        unlink($mapImageName);
        
        $this->processTemplateRegionsAndFields($pdf, $template, $jobData);

        $this->collectedLegends[] = $this->legendHandler->collectLegends($jobData);

        return $pdf;
    }

    /**
     * Process after main map for multiframe print
     * Handles legends and finalization without duplicating textfields/regions
     * 
     * @param PDF_Extensions|\FPDF $pdf PDF object
     * @param Template|array $template Template data
     * @param array $jobData Job data
     */
    protected function afterMainMapMulti(PDF_Extensions|\FPDF $pdf, Template|array $template, array $jobData): void
    {
        $legends = $this->mergeCollectedLegends();

        $this->handleMainPageLegends($pdf, $template, $jobData, $legends);
        $this->finishMainPage($pdf, $template, $jobData);
        $this->handleRemainingLegends($pdf, $template, $jobData, $legends);
    }

    /**
     * Merge collected legends from all frames
     * Deduplicates legend blocks by title across all frames
     * 
     * @return array Merged legend block groups
     */
    protected function mergeCollectedLegends(): array
    {
        // Collect all unique legend blocks by title
        $uniqueBlocks = [];
        
        foreach ($this->collectedLegends as $collectedLegend) {
            foreach ($collectedLegend as $legendBlockGroup) {
                foreach ($legendBlockGroup->iterateBlocks() as $block) {
                    $uniqueBlocks[$block->getTitle()] = $block;
                }
            }
        }

        // Return as a single merged legend group
        return [new LegendBlockGroup($uniqueBlocks)];
    }
}
