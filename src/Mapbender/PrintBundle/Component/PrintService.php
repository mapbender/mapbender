<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\FeatureTransform;
use Mapbender\PrintBundle\Component\Region\A4FullPage;
use Mapbender\PrintBundle\Component\Service\PrintPluginHost;
use Mapbender\PrintBundle\Component\Service\PrintServiceInterface;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;
use Psr\Log\LoggerInterface;

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
    /** @var PDF_Extensions|\FPDF */
    protected $pdf;
    /** @var Template|array */
    protected $conf;

    /** @var array */
    protected $data;

    /** @var string */
    protected $resourceDir;
    /** @var string */
    protected $tempDir;
    /** @var OdgParser */
    protected $templateParser;
    /** @var PrintPluginHost */
    protected $pluginHost;
    /** @var ImageTransport */
    protected $imageTransport;

    /**
     * @param LayerRenderer[] $layerRenderers
     * @param ImageTransport $imageTransport
     * @param OdgParser $templateParser
     * @param PrintPluginHost $pluginHost
     * @param LoggerInterface $logger
     * @param string $resourceDir
     * @param string|null $tempDir absolute path or emptyish to autodetect via sys_get_temp_dir()
     */
    public function __construct($layerRenderers, ImageTransport $imageTransport,
                                $templateParser, $pluginHost, $logger,
                                $resourceDir, $tempDir)
    {
        $this->templateParser = $templateParser;
        $this->imageTransport = $imageTransport;

        $this->pluginHost = $pluginHost;
        $this->resourceDir = $resourceDir;
        $this->tempDir = $tempDir ?: sys_get_temp_dir();
        parent::__construct($layerRenderers, $logger);
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
        $templateData = $this->getTemplateData($jobData);
        $this->setup($templateData, $jobData);

        $mapImageName = $this->createMapImage($templateData, $jobData);

        $pdf = $this->buildPdf($mapImageName, $templateData, $jobData);

        return $this->dumpPdf($pdf);
    }

    /**
     * Executes the job (plain array), returns a binary string representation of the resulting PDF.
     *
     * @param array $jobData
     * @return string
     * @throws \Exception on invalid template
     */
    public function dumpPrint(array $jobData)
    {
        return $this->doPrint($jobData);
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
        if (!file_put_contents($fileName, $this->doPrint($jobData))) {
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

    /**
     * @param array $templateData
     * @param array $jobData
     * @return string path to stored image
     */
    private function createMapImage($templateData, $jobData)
    {
        $targetBox = $this->getTargetBox($templateData, $jobData);
        $exportJob = array_replace($jobData, $targetBox->getAbsWidthAndHeight());
        $mapImage = $this->buildExportImage($exportJob);

        // dump to file system immediately to recoup some memory before building PDF
        $mapImageName = $this->makeTempFile('mb_print_final');
        imagepng($mapImage, $mapImageName);
        imagedestroy($mapImage);
        return $mapImageName;
    }

    /**
     * @param Template|array $templateData
     * @param string $templateName
     * @return \FPDF|\FPDF_TPL|PDF_Extensions
     * @throws \Exception
     */
    protected function makeBlankPdf($templateData, $templateName)
    {
        require_once('PDF_Extensions.php');

        /** @var PDF_Extensions|\FPDF|\FPDF_TPL $pdf */
        $pdf =  new PDF_Extensions();
        $pdfFile = $this->resourceDir . '/templates/' . $templateName . '.pdf';
        $pdf->setSourceFile($pdfFile);
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
     * @return \FPDF|\FPDF_TPL|PDF_Extensions
     * @throws \Exception
     */
    protected function buildPdf($mapImageName, $templateData, $jobData)
    {
        // @todo: eliminate instance variable $this->pdf
        $this->pdf = $pdf = $this->makeBlankPdf($templateData, $jobData['template']);
        $tplidx = $pdf->importPage(1);
        $hasTransparentBg = $this->checkPdfBackground($pdf);
        if (!$hasTransparentBg){
            $pdf->useTemplate($tplidx);
        }
        $this->addMapImage($pdf, $mapImageName, $templateData);
        unlink($mapImageName);

        if ($hasTransparentBg) {
            $pdf->useTemplate($tplidx);
        }

        $this->afterMainMap($pdf, $templateData, $jobData);

        // add legend
        if (!empty($jobData['legends'])) {
            $this->addLegend();
        }
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
     * @param \FPDF|\FPDF_TPL|PDF_Extensions $pdf
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
     * @see afterMainMap
     * @see handleRegion
     *
     * @param array $jobData
     * @return string[]
     */
    protected function getFirstPageSpecialRegionNames($jobData)
    {
        return  array(
            // Map is already rendered (c.f. method name xD)
            'map',
            // Legend can perform page breaks, which means it must wait until all other
            // regions are handled
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
     * @param \FPDF|\FPDF_TPL|PDF_Extensions $pdf
     * @param Template $template
     * @param array $jobData
     */
    protected function afterMainMap($pdf, $template, $jobData)
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
     * Should populate a TemplateRegion on the first page of the PDF being generated.
     * Nothing happening in this method or called by it should add page breaks to the pdf.
     *
     * @param \FPDF|\FPDF_TPL|PDF_Extensions $pdf
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
     * Fills textual regions on the first page.
     *
     * @param \FPDF|\FPDF_TPL|PDF_Extensions $pdf
     * @param Template|array $template
     * @param array $jobData
     */
    protected function addTextFields($pdf, $template, $jobData)
    {
        foreach ($template->getTextFields() as $fieldName => $region) {
            // skip extent fields, see special handling in addCoordinates method
            if (preg_match("/^extent/", $fieldName)) {
                continue;
            }
            $text = $this->getTextFieldContent($fieldName, $jobData);
            if ($text !== null) {
                $this->applyFontStyle($pdf, $region);
                $pdf->SetXY($region['x'] - 1, $region['y']);
                $pdf->MultiCell($region['width'], $region['height'], utf8_decode($text));
            }
        }
        // reset text color to default black
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * @param \FPDF|\FPDF_TPL|PDF_Extensions $pdf
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
            case 'date' :
                return date('d.m.Y');
            case 'scale' :
                return '1 : ' . $jobData['scale_select'];
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
            $transColor = imagecolorallocatealpha($image, 255, 255, 255, 0);
            $rotatedImage = imagerotate($image, $rotation, $transColor);
            $srcSize = array(imagesx($image), imagesy($image));
            $destSize = array(imagesx($rotatedImage), imagesy($rotatedImage));
            $x = abs(($srcSize[0] - $destSize[0]) / 2);
            $y = abs(($srcSize[1] - $destSize[1]) / 2);
            $northarrow = $this->cropImage($rotatedImage, $x, $y, $srcSize[0], $srcSize[1]);
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
        // fix job data to be compatible with image export:
        // 1: 'changeAxis' is only on top level, not per layer
        // 2: Layer type is missing, we only have a URL
        // 3: opacity is missing
        // 4: pixel width is inferred from height + template region aspect ratio
        $layerDefs = array();
        foreach ($ovData['layers'] as $layerUrl) {
            $layerDefs[] = array(
                'url' => $layerUrl,
                'type' => 'wms',        // HACK (same behavior as old code)
                'changeAxis' => $ovData['changeAxis'],
                'opacity' => 1,
            );
        }
        $cnt = $ovData['center'];
        $ovWidth = $ovData['height'] * $region['width'] / $region['height'];
        $ovExtent = Box::fromCenterAndSize($cnt['x'], $cnt['y'], $ovWidth, $ovData['height']);
        $image = $this->buildExportImage(array(
            'layers' => $layerDefs,
            'width' => $ovImageWidth,
            'height' => $ovImageHeight,
            'extent' => $ovExtent->getAbsWidthAndHeight(),
            'center' => $ovExtent->getCenterXy(),
        ));

        $ovTransform = FeatureTransform::boxToBox($ovExtent, $ovPixelBox, 1.0);
        $red = imagecolorallocate($image,255,0,0);
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
        $pdf->SetFont('arial', '', 10 );

        $length = 0.01 * $jobData['scale_select'] * 5;
        $suffix = 'm';

        $pdf->Text($region['x'] , $region['y'] - 1 , '0' );
        $pdf->Text($region['x'] + $totalWidth - 7, $region['y'] - 1 , $length . '' . $suffix);

        $nSections = 5;
        $sectionWidth = $totalWidth / $nSections;

        $pdf->SetLineWidth(0.1);
        $pdf->SetDrawColor(0, 0, 0);
        for ($i = 0; $i < $nSections; ++$i) {
            if ($i & 1) {
                $pdf->SetFillColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(0, 0, 0);
            }
            $pdf->Rect($region['x'] + round($i * $sectionWidth), $region['y'], $sectionWidth, 2, 'FD');
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

    private function addLegend()
    {
        // @todo: config values please
        $marginsFirstPage = array(
            'x' => 5,
            'y' => 5,
        );
        $marginsFullPage = array(
            'x' => 5,
            'y' => 10,
        );
        if (!empty($this->conf['legend'])) {
            $regionData = $this->conf['legend'];
            $offsetXy = array($regionData['x'], $regionData['y']);
            $margins = $marginsFirstPage;
            $region = new TemplateRegion($regionData['width'], $regionData['height'], $offsetXy);
        } else {
            // print legend on second page
            $this->pdf->addPage('P');
            $this->pdf->SetFont('Arial', 'B', 11);
            $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
            $margins = $marginsFullPage;
            $region = new A4FullPage();
        }
        $x = $margins['x'];
        $y = $margins['y'];

        $blocks = array();
        foreach ($this->data['legends'] as $idx => $legendArray) {
            foreach ($legendArray as $title => $legendUrl) {
                $image = $this->imageTransport->downloadImage($legendUrl);
                if ($image) {
                   $blocks[] = new LegendBlock($image, $title);
                }
            };
        }
        foreach ($blocks as $n => $block) {
                $size  = array($block->getWidth(), $block->getHeight());
                $tempY = round($size[1] * 25.4 / 96) + 10;

                if ($n > 0) {
                    if ($y + $tempY + 10 > $region->getHeight()) {
                        // spill to next column
                        $x += 105;
                        $y = $margins['y'];
                    }
                    if ($x + 20 > $region->getWidth()) {
                        // we need a page break
                        $this->pdf->addPage('P');
                        $this->pdf->SetFont('Arial', 'B', 11);
                        $region = new A4FullPage();
                        $margins = $marginsFullPage;
                        $x = $margins['x'];
                        $y = $margins['y'];
                        $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
                    }
                }

                $pageX = $x + $region->getOffsetX();
                $pageY = $y + $region->getOffsetY();
                $this->pdf->SetXY($pageX, $pageY);
                $this->pdf->Cell(0,0,  utf8_decode($block->getTitle()));
                $this->addImageToPdf($this->pdf, $block->resource,
                    $pageX,
                    $pageY + 5,
                    ($size[0] * 25.4 / 96), ($size[1] * 25.4 / 96));

                $y += round($size[1] * 25.4 / 96) + 10;
        }
    }


    private function getLegendImage($url)
    {
        $imagename = $this->makeTempFile('mb_printlegend');
        $image = $this->imageTransport->downloadImage($url);
        if ($image) {
            imagepng($image, $imagename);
            imagedestroy($image);
            return $imagename;
        } else {
            return null;
        }
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param array $templateData
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
            $this->addImageToPdf($pdf, $sourcePath, $region['x'], $region['y'], 0, $region['height']);
        } else {
            $defaultPath = $this->resourceDir . '/images/legendpage_image.png';
            if ($defaultPath !== $sourcePath && file_exists($defaultPath)) {
                $this->addImageToPdf($pdf, $defaultPath, $region['x'], $region['y'], 0, $region['height']);
            }
        }
    }

    private function checkPdfBackground($pdf) {
        $pdfArray = (array) $pdf;
        $pdfFile = $pdfArray['currentFilename'];
        $pdfSubArray = (array) $pdfArray['parsers'][$pdfFile];
        $prefix = chr(0) . '*' . chr(0);
        $pdfSubArray2 = $pdfSubArray[$prefix . '_root'][1][1];

        if (sizeof($pdfSubArray2) > 0 && !array_key_exists('/Outlines', $pdfSubArray2)) {
            return true;
        }

        return false;
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param resource|string $gdResOrPath
     * @param int $xOffset
     * @param int $yOffset
     * @param int $width optional, to rescale image
     * @param int $height optional, to rescale image
     */
    protected function addImageToPdf($pdf, $gdResOrPath, $xOffset, $yOffset, $width=0, $height=0)
    {
        if (is_resource($gdResOrPath)) {
            $imageName = $this->makeTempFile('mb_print_pdfbuild');
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
    protected function addImageToPdfRegion($pdf, $gdResOrPath, $region)
    {
        $this->addImageToPdf($pdf, $gdResOrPath,
            $region->getOffsetX(), $region->getOffsetY(),
            $region->getWidth(), $region->getHeight());
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
     * @param $prefix
     * @return string
     */
    protected function makeTempFile($prefix)
    {
        $filePath = tempnam($this->tempDir, $prefix);
        // tempnam may return false in undocumented error cases
        if (!$filePath) {
            throw new \RuntimeException("Failed to create temp file with prefix '$prefix' in '{$this->tempDir}'");
        }
        return $filePath;
    }
}
