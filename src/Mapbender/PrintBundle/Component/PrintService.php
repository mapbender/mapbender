<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\FeatureTransform;
use Mapbender\PrintBundle\Component\Service\PrintPluginHost;
use Mapbender\PrintBundle\Component\Service\PrintServiceInterface;
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
    protected $conf;

    /** @var array */
    protected $data;

    /** @var OdgParser */
    protected $templateParser;
    /** @var PrintPluginHost */
    protected $pluginHost;

    /**
     * @var array Default geometry style
     */
    protected $defaultStyle = array(
        "strokeWidth" => 1
    );

    /**
     * @param OdgParser $templateParser
     * @param PrintPluginHost $pluginHost
     * @param LoggerInterface $logger
     * @param string $resourceDir
     * @param string|null $tempDir
     */
    public function __construct($imageTransport,
                                $templateParser, $pluginHost, $logger,
                                $resourceDir, $tempDir)
    {
        $this->templateParser = $templateParser;
        $this->pluginHost = $pluginHost;
        parent::__construct($imageTransport, $resourceDir, $tempDir, $logger);
    }

    public function doPrint($jobData)
    {
        $templateData = $this->getTemplateData($jobData);
        $this->setup($templateData, $jobData);

        $mapImageName = $this->createMapImage($templateData, $jobData);

        $pdf = $this->buildPdf($mapImageName, $templateData, $jobData);

        return $this->dumpPdf($pdf);
    }

    public function dumpPrint(array $jobData)
    {
        return $this->doPrint($jobData);
    }

    public function storePrint(array $jobData, $fileName)
    {
        if (!file_put_contents($fileName, $this->doPrint($jobData))) {
            throw new \RuntimeException("Failed to store printout at {$fileName}");
        }
    }

    /**
     * @param array $jobData
     * @return array
     */
    protected function getTemplateData($jobData)
    {
        return $this->templateParser->getConf($jobData['template']);
    }

    /**
     * @param array $templateData
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
     * @param $templateData
     * @param $templateName
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
     * @param array $templateData
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
     * @param array $templateData
     */
    protected function addMapImage($pdf, $mapImageName, $templateData)
    {
        $region = $templateData['map'];
        $this->addImageToPdf($pdf, $mapImageName, $region['x'], $region['y'], $region['width'], $region['height']);
        // add map border (default is black)
        $pdf->Rect($region['x'], $region['y'], $region['width'], $region['height']);
    }

    /**
     * Renders the remaining regions on the first page after the main map image has been added.
     * This excludes the legend, because the legend rendering process, if it begins on the first
     * page, may spill over and start adding more pages.
     *
     * @param \FPDF|\FPDF_TPL|PDF_Extensions $pdf
     * @param array $templateData
     * @param array $jobData
     */
    protected function afterMainMap($pdf, $templateData, $jobData)
    {
        // add northarrow
        if (!empty($templateData['northarrow'])) {
            $this->addNorthArrow($pdf, $templateData, $jobData);
        }

        if (!empty($templateData['fields'])) {
            $this->addTextFields($pdf, $templateData, $jobData);
        }

        // add overview map
        if (!empty($jobData['overview']) && !empty($templateData['overview'])) {
            $this->addOverviewMap($pdf, $templateData, $jobData);
        }

        // add scalebar
        if (!empty($templateData['scalebar'])) {
            $this->addScaleBar($pdf, $templateData, $jobData);
        }

        // add coordinates
        if (isset($templateData['fields']['extent_ur_x']) && isset($templateData['fields']['extent_ur_y'])
                && isset($templateData['fields']['extent_ll_x']) && isset($templateData['fields']['extent_ll_y']))
        {
            $this->addCoordinates();
        }

        // add dynamic logo
        if (!empty($templateData['dynamic_image']) && !empty($templateData['dynamic_image'])) {
            $this->addDynamicImage();
        }

        // add dynamic text
        if (!empty($templateData['fields']['dynamic_text']) && !empty($templateData['dynamic_text'])) {
            $this->addDynamicText();
        }
    }

    /**
     * Fills textual regions on the first page.
     *
     * @param \FPDF|\FPDF_TPL|PDF_Extensions $pdf
     * @param array $templateData
     * @param array $jobData
     */
    protected function addTextFields($pdf, $templateData, $jobData)
    {
        foreach ($templateData['fields'] as $fieldName => $region) {
            // skip extent fields, see special handling in addCoordinates method
            if (preg_match("/^extent/", $fieldName)) {
                continue;
            }
            $text = $this->getTextFieldContent($fieldName, $jobData);
            if ($text !== null) {
                list($r, $g, $b) = CSSColorParser::parse($region['color']);
                $pdf->SetTextColor($r, $g, $b);
                $pdf->SetFont('Arial', '', intval($region['fontsize']));
                $pdf->SetXY($region['x'] - 1, $region['y']);
                $pdf->MultiCell($region['width'], $region['height'], utf8_decode($text));
            }
        }
        // reset text color to default black
        $pdf->SetTextColor(0, 0, 0);
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
            default:
                if (isset($jobData['extra'][$fieldName])) {
                    return $jobData['extra'][$fieldName];
                } else {
                    // @todo: log warning?
                    return null;
                }
        }
    }

    protected function addNorthArrow($pdf, $templateData, $jobData)
    {
        $northarrow = $this->resourceDir . '/images/northarrow.png';
        $rotation = intval($jobData['rotation']);

        $region = $templateData['northarrow'];
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
        $this->addImageToPdf($pdf, $northarrow, $region['x'], $region['y'], $region['width'], $region['height']);
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param array $templateData
     * @param array $jobData
     */
    protected function addOverviewMap($pdf, $templateData, $jobData)
    {
        $ovData = $jobData['overview'];
        $region = $templateData['overview'];
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

        $this->addImageToPdf($pdf, $image, $region['x'], $region['y'], $region['width'], $region['height']);
        imagecolordeallocate($image, $red);
        imagedestroy($image);
        // draw border rectangle
        $pdf->Rect($region['x'], $region['y'], $region['width'], $region['height']);
    }

    /**
     * @param PDF_Extensions|\FPDF $pdf
     * @param array $templateData
     * @param array $jobData
     */
    protected function addScaleBar($pdf, $templateData, $jobData)
    {
        $region = $templateData['scalebar'];
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
    }

    private function addCoordinates()
    {
        $pdf = $this->pdf;

        $corrFactor = 2;
        $precision = 2;
        // correction factor and round precision if WGS84
        if($this->data['extent']['width'] < 1){
             $corrFactor = 3;
             $precision = 6;
        }

        // upper right Y
        $pdf->SetFont('Arial', '', intval($this->conf['fields']['extent_ur_y']['fontsize']));
        $pdf->Text($this->conf['fields']['extent_ur_y']['x'] + $corrFactor,
                    $this->conf['fields']['extent_ur_y']['y'] + 3,
                    round($this->data['extent_feature'][2]['y'], $precision));

        // upper right X
        $pdf->SetFont('Arial', '', intval($this->conf['fields']['extent_ur_x']['fontsize']));
        $pdf->TextWithDirection($this->conf['fields']['extent_ur_x']['x'] + 1,
                    $this->conf['fields']['extent_ur_x']['y'],
                    round($this->data['extent_feature'][2]['x'], $precision),'D');

        // lower left Y
        $pdf->SetFont('Arial', '', intval($this->conf['fields']['extent_ll_y']['fontsize']));
        $pdf->Text($this->conf['fields']['extent_ll_y']['x'],
                    $this->conf['fields']['extent_ll_y']['y'] + 3,
                    round($this->data['extent_feature'][0]['y'], $precision));

        // lower left X
        $pdf->SetFont('Arial', '', intval($this->conf['fields']['extent_ll_x']['fontsize']));
        $pdf->TextWithDirection($this->conf['fields']['extent_ll_x']['x'] + 3,
                    $this->conf['fields']['extent_ll_x']['y'] + 30,
                    round($this->data['extent_feature'][0]['x'], $precision),'U');
    }

    private function addDynamicImage()
    {
        $dynImage = $this->resourceDir . '/' . $this->data['dynamic_image']['path'];
        if(file_exists ($dynImage)){
            $this->pdf->Image($dynImage,
                            $this->conf['dynamic_image']['x'],
                            $this->conf['dynamic_image']['y'],
                            0,
                            $this->conf['dynamic_image']['height'],
                            'png');
            return;
        }

    }

    private function addDynamicText()
    {
        $this->pdf->SetFont('Arial', '', $this->conf['fields']['dynamic_text']['fontsize']);
        $this->pdf->MultiCell($this->conf['fields']['dynamic_text']['width'],
                $this->conf['fields']['dynamic_text']['height'],
                utf8_decode($this->data['dynamic_text']['text']));
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
        if(isset($this->conf['legend']) && $this->conf['legend']){
          // print legend on first
          $height = $this->conf['legend']['height'];
          $width = $this->conf['legend']['width'];
          $xStartPosition = $this->conf['legend']['x'];
          $yStartPosition = $this->conf['legend']['y'];
          $x = $xStartPosition + 5;
          $y = $yStartPosition + 5;
          $legendConf = true;
        }else{
          // print legend on second page
          $this->pdf->addPage('P');
          $this->pdf->SetFont('Arial', 'B', 11);
          $x = 5;
          $y = 10;
          $height = $this->pdf->getHeight();
          $width = $this->pdf->getWidth();
          $legendConf = false;
          $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
          $xStartPosition = 0;
          $yStartPosition = 0;
        }

        foreach ($this->data['legends'] as $idx => $legendArray) {
            $c         = 1;
            $arraySize = count($legendArray);
            foreach ($legendArray as $title => $legendUrl) {

                if (preg_match('/request=GetLegendGraphic/i', urldecode($legendUrl)) === 0) {
                    continue;
                }

                $image = $this->getLegendImage($legendUrl);
                if (!$image) {
                    continue;
                }
                $size  = getimagesize($image);
                $tempY = round($size[1] * 25.4 / 96) + 10;

                if ($c > 1) {
                    // print legend on second page
                    if($y + $tempY + 10 > ($this->pdf->getHeight()) && $legendConf == false){
                        $x += 105;
                        $y = 10;
                        $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
                        if($x + 20 > ($this->pdf->getWidth())){
                            $this->pdf->addPage('P');
                            $x = 5;
                            $y = 10;
                            $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
                        }
                    }


                    // print legend on first page
                    if($legendConf == true){
                        if(($y-$yStartPosition) + $tempY + 10 > $height && $width > 100){
                            $x += $x + 105;
                            $y = $yStartPosition + 5;
                            if($x - $xStartPosition + 20 > $width){
                                $this->pdf->addPage('P');
                                $x = 5;
                                $y = 10;
                                $legendConf = false;
                                $this->addLegendPageImage($this->pdf, $this->conf, $this->data);

                            }
                        }else if (($y-$yStartPosition) + $tempY + 10 > $height){
                                $this->pdf->addPage('P');
                                $x = 5;
                                $y = 10;
                                $legendConf = false;
                                $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
                        }
                    }
                }


                if ($legendConf == true) {
                    // add legend in legend region on first page
                    // To Be doneCell(0,0,  utf8_decode($title));
                    $this->pdf->SetXY($x,$y);
                    $this->pdf->Cell(0,0,  utf8_decode($title));
                    $this->pdf->Image($image,
                                $x,
                                $y +5 ,
                                ($size[0] * 25.4 / 96), ($size[1] * 25.4 / 96), 'png', '', false, 0);

                        $y += round($size[1] * 25.4 / 96) + 10;
                        if(($y - $yStartPosition + 10 ) > $height && $width > 100){
                            $x +=  105;
                            $y = $yStartPosition + 10;
                        }
                        if(($x - $xStartPosition + 10) > $width && $c < $arraySize ){
                            $this->pdf->addPage('P');
                            $x = 5;
                            $y = 10;
                            $this->pdf->SetFont('Arial', 'B', 11);
                            $height = $this->pdf->getHeight();
                            $width = $this->pdf->getWidth();
                            $legendConf = false;
                            $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
                        }

                  }else{
                      // print legend on second page
                      $this->pdf->SetXY($x,$y);
                      $this->pdf->Cell(0,0,  utf8_decode($title));
                      $this->pdf->Image($image, $x, $y + 5, ($size[0] * 25.4 / 96), ($size[1] * 25.4 / 96), 'png', '', false, 0);

                      $y += round($size[1] * 25.4 / 96) + 10;
                      if($y > ($this->pdf->getHeight())){
                          $x += 105;
                          $y = 10;
                      }
                      if($x + 20 > ($this->pdf->getWidth()) && $c < $arraySize){
                          $this->pdf->addPage('P');
                          $x = 5;
                          $y = 10;
                          $this->addLegendPageImage($this->pdf, $this->conf, $this->data);
                      }

                  }

                unlink($image);
                $c++;
            }
        }
    }


    private function getLegendImage($url)
    {
        $imagename = $this->makeTempFile('mb_printlegend');
        $image = $this->downloadImage($url);
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
     * @return PrintPluginHost
     */
    protected function getPluginHost()
    {
        return $this->pluginHost;
    }
}
