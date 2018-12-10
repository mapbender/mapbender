<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Affine2DTransform;
use Mapbender\PrintBundle\Component\Export\Box;

/**
 * Mapbender3 Print Service.
 *
 * @author Stefan Winkelmann
 */
class PrintService extends ImageExportService
{
    /** @var PDF_Extensions|\FPDF */
    protected $pdf;
    protected $conf;
    protected $rotation;
    protected $finalImageName;
    protected $user;

    /** @var Affine2DTransform */
    protected $featureTransform;
    /** @var Box */
    protected $mapRequestBox;
    /** @var Box */
    protected $targetBox;
    /** @var Box */
    protected $canvasBox;
    /** @var array */
    protected $data;

    /**
     * @var array Default geometry style
     */
    protected $defaultStyle = array(
        "strokeWidth" => 1
    );

    public function doPrint($data)
    {
        $this->setup($data);

        $this->createMapImage();

        return $this->buildPdf();
    }

    /**
     * @param array $jobData
     * @return Box
     */
    protected function initializeCanvasBox(array $jobData)
    {
        $rotatedBox = $this->targetBox->getExpandedForRotation(intval($jobData['rotation']));
        $rotatedBox->roundToIntegerBoundaries();
        // re-anchor rotated box to put top left at (0,0); note: gd pixel coords are top down
        return new Box(0, abs($rotatedBox->getHeight()), abs($rotatedBox->getWidth()), 0);
    }

    /**
     * @param array $jobData
     * @return Box
     */
    protected function initializeMapRequestBox(array $jobData)
    {
        $width = $jobData['extent']['width'];
        $height = $jobData['extent']['height'];
        $centerx = $jobData['center']['x'];
        $centery = $jobData['center']['y'];
        $unrotatedBox = Box::fromCenterAndSize($centerx, $centery, $width, $height);
        return $unrotatedBox->getExpandedForRotation(intval($jobData['rotation']));
    }

    protected function preprocessRasterUrl($layerDef, $width, $height)
    {
        $request = strstr($layerDef['url'], '&BBOX', true);

        $widthParam = '&WIDTH=' . abs($this->canvasBox->getWidth());
        $heightParam =  '&HEIGHT=' . abs($this->canvasBox->getHeight());

        $mExt = $this->mapRequestBox;
        if (!empty($layerDef['changeAxis'])){
            $request .= '&BBOX=' . $mExt->bottom . ',' . $mExt->left . ',' . $mExt->top . ',' . $mExt->right;
        } else {
            $request .= '&BBOX=' . $mExt->left . ',' . $mExt->bottom . ',' . $mExt->right . ',' . $mExt->top;
        }

        $request .= $widthParam . $heightParam;

        if (!isset($this->data['replace_pattern'])){
            if ($this->data['quality'] != '72') {
                $request .= '&map_resolution=' . $this->data['quality'];
            }
        } else {
            $request = $this->addReplacePattern($request, $this->data['quality']);
        }
        return $request;
    }

    private function setup($data)
    {
        $this->user      = $data['user'];

        // data from client
        $this->data = $data;

        // template configuration from odg
        $odgParser = new OdgParser($this->container);
        $this->conf = $conf = $odgParser->getConf($data['template']);

        $targetWidth = round($conf['map']['width'] / 25.4 * $data['quality']);
        $targetHeight = round($conf['map']['height'] / 25.4 * $data['quality']);
        $this->mapRequestBox = $this->initializeMapRequestBox($data);
        // NOTE: gd pixel coords are top down
        $this->targetBox = new Box(0, $targetHeight, $targetWidth, 0);
        $this->canvasBox = $this->initializeCanvasBox($data);
        $this->featureTransform = Affine2DTransform::boxToBox($this->mapRequestBox, $this->canvasBox);
        $this->rotation = intval($data['rotation']);
    }

    private function addReplacePattern($url, $dpi)
    {
        $default = '';
        foreach ($this->data['replace_pattern'] as $pattern) {
            if (isset($pattern['default'])){
                if (isset($pattern['default'][$dpi])){
                    $default = $pattern['default'][$dpi];
                }
            } elseif (strpos($url, $pattern['pattern']) !== false){
                if (isset($pattern['replacement'][$dpi])){
                    $url = str_replace($pattern['pattern'], $pattern['replacement'][$dpi], $url);
                    $signer = $this->container->get('signer');
                    return $signer->signUrl($url);
                }
            }
        }
        return $url . $default;
    }

    private function createMapImage()
    {
        $rotation = $this->rotation;
        $neededImageWidth = abs($this->canvasBox->getWidth());
        $neededImageHeight = abs($this->canvasBox->getHeight());
        $targetImage = $this->buildExportImage(array(
            'layers' => $this->data['layers'],
            'width' => $neededImageWidth,
            'height' => $neededImageHeight,
        ));

        $this->finalImageName = $this->makeTempFile('mb_print_final');
        if ($rotation) {
            $clippedImage = $this->rotateAndCrop($targetImage, $rotation);
            imagepng($clippedImage, $this->finalImageName);
            imagedestroy($clippedImage);
            imagedestroy($targetImage);
        } else {
            imagepng($targetImage, $this->finalImageName);
            imagedestroy($targetImage);
        }
    }

    /**
     * @param resource GDish $sourceImage
     * @param number $rotation
     * @return resource GD image
     */
    protected function rotateAndCrop($sourceImage, $rotation)
    {
        $imageWidth = $this->targetBox->getWidth();
        $imageHeight = abs($this->targetBox->getHeight());

        $transColor = imagecolorallocatealpha($sourceImage, 255, 255, 255, 127);
        $rotatedImage = imagerotate($sourceImage, $rotation, $transColor);
        imagealphablending($rotatedImage, false);
        imagesavealpha($rotatedImage, true);

        $offsetX = (imagesx($rotatedImage) - $this->targetBox->getWidth()) * 0.5;
        $offsetY = (imagesy($rotatedImage) - abs($this->targetBox->getHeight())) * 0.5;

        $clippedImage = imagecreatetruecolor($imageWidth, $imageHeight);
        imagealphablending($clippedImage, false);
        imagesavealpha($clippedImage, true);
        imagecopy($clippedImage, $rotatedImage, 0, 0, $offsetX, $offsetY,
            $imageWidth, $imageHeight);
        imagedestroy($rotatedImage);
        unset($rotatedImage);
        return $clippedImage;
    }

    private function buildPdf()
    {
        require_once('PDF_Extensions.php');

        // set format
        if($this->conf['orientation'] == 'portrait'){
            $format = array($this->conf['pageSize']['width'],$this->conf['pageSize']['height']);
            $orientation = 'P';
        }else{
            $format = array($this->conf['pageSize']['height'],$this->conf['pageSize']['width']);
            $orientation = 'L';
        }

        /** @var PDF_Extensions|\FPDF|\FPDF_TPL $pdf */
        $this->pdf = $pdf = new PDF_Extensions();

        $template = $this->data['template'];
        $pdfFile = $this->resourceDir . '/templates/' . $template . '.pdf';
        $pdf->setSourceFile($pdfFile);
        $tplidx = $pdf->importPage(1);
        $pdf->SetAutoPageBreak(false);
        $pdf->addPage($orientation, $format);

        $hasTransparentBg = $this->checkPdfBackground($pdf);
        if ($hasTransparentBg == false){
            $pdf->useTemplate($tplidx);
        }

        // add final map image
        $mapUlX = $this->conf['map']['x'];
        $mapUlY = $this->conf['map']['y'];
        $mapWidth = $this->conf['map']['width'];
        $mapHeight = $this->conf['map']['height'];

        $pdf->Image($this->finalImageName, $mapUlX, $mapUlY,
                $mapWidth, $mapHeight, 'png', '', false, 0, 5, -1 * 0);
        // add map border (default is black)
        $pdf->Rect($mapUlX, $mapUlY, $mapWidth, $mapHeight);
        unlink($this->finalImageName);

        if ($hasTransparentBg == true){
            $pdf->useTemplate($tplidx);
        }

        // add northarrow
        if (isset($this->conf['northarrow'])) {
            $this->addNorthArrow();
        }

        // fill text fields
        if (isset($this->conf['fields']) ) {
            foreach ($this->conf['fields'] as $k => $v) {
                list($r, $g, $b) = CSSColorParser::parse($this->conf['fields'][$k]['color']);
                $pdf->SetTextColor($r,$g,$b);
                $pdf->SetFont('Arial', '', intval($this->conf['fields'][$k]['fontsize']));
                $pdf->SetXY($this->conf['fields'][$k]['x'] - 1,
                    $this->conf['fields'][$k]['y']);

                // continue if extent field is set
                if(preg_match("/^extent/", $k)){
                    continue;
                }

                switch ($k) {
                    case 'date' :
                        $date = new \DateTime;
                        $pdf->Cell($this->conf['fields']['date']['width'],
                            $this->conf['fields']['date']['height'],
                            $date->format('d.m.Y'));
                        break;
                    case 'scale' :
                        $pdf->Cell($this->conf['fields']['scale']['width'],
                            $this->conf['fields']['scale']['height'],
                            '1 : ' . $this->data['scale_select']);
                        break;
                    default:
                        if (isset($this->data['extra'][$k])) {
                            $pdf->MultiCell($this->conf['fields'][$k]['width'],
                                $this->conf['fields'][$k]['height'],
                                utf8_decode($this->data['extra'][$k]));
                        }

                        // fill digitizer feature fields
                        if (isset($this->data['digitizer_feature']) && preg_match("/^feature./", $k)) {
                            $dfData = $this->data['digitizer_feature'];
                            $feature = $this->getFeature($dfData['schemaName'], $dfData['id']);
                            $attribute = substr(strrchr($k, "."), 1);

                            if ($feature && $attribute) {
                                $pdf->MultiCell($this->conf['fields'][$k]['width'],
                                    $this->conf['fields'][$k]['height'],
                                    utf8_decode($feature->getAttribute($attribute)));
                            }
                        }
                        break;
                }
            }
        }

        // reset text color to default black
        $pdf->SetTextColor(0,0,0);

        // add overview map
        if (isset($this->data['overview']) && isset($this->conf['overview']) ) {
            $this->addOverviewMap($this->data['overview']);
        }

        // add scalebar
        if (isset($this->conf['scalebar']) ) {
            $this->addScaleBar();
        }

        // add coordinates
        if (isset($this->conf['fields']['extent_ur_x']) && isset($this->conf['fields']['extent_ur_y'])
                && isset($this->conf['fields']['extent_ll_x']) && isset($this->conf['fields']['extent_ll_y']))
        {
            $this->addCoordinates();
        }

        // add dynamic logo
        if (isset($this->conf['dynamic_image']) && $this->conf['dynamic_image']){
            $this->addDynamicImage();
        }

        // add dynamic text
        if (isset($this->conf['fields'])
            && isset($this->conf['fields']['dynamic_text'])
            && $this->conf['fields']['dynamic_text']){
            $this->addDynamicText();
        }

        // add legend
        if (isset($this->data['legends']) && !empty($this->data['legends'])){
            $this->addLegend();
        }

        return $pdf->Output(null, 'S');
    }

    private function addNorthArrow()
    {
        $northarrow = $this->resourceDir . '/images/northarrow.png';
        $rotation = $this->rotation;
        $rotatedImageName = null;

        if($rotation != 0){
            $image = imagecreatefrompng($northarrow);
            $transColor = imagecolorallocatealpha($image, 255, 255, 255, 0);
            $rotatedImage = imagerotate($image, $rotation, $transColor);
            $rotatedImageName = $this->makeTempFile('mb_northarrow');
            imagepng($rotatedImage, $rotatedImageName);

            if ($rotation == 90 || $rotation == 270) {
                //
            } else {
                $srcImage = imagecreatefrompng($rotatedImageName);
                $srcSize = getimagesize($rotatedImageName);
                $destSize = getimagesize($northarrow);
                $x = ($srcSize[0] - $destSize[0]) / 2;
                $y = ($srcSize[1] - $destSize[1]) / 2;
                $destImage = imagecreatetruecolor($destSize[0], $destSize[1]);
                imagecopy($destImage, $srcImage, 0, 0, $x, $y, $srcSize[0], $srcSize[1]);
                imagepng($destImage, $rotatedImageName);
            }
            $northarrow = $rotatedImageName;
        }

        $this->pdf->Image($northarrow,
                            $this->conf['northarrow']['x'],
                            $this->conf['northarrow']['y'],
                            $this->conf['northarrow']['width'],
                            $this->conf['northarrow']['height'],
                            'png');
        if($rotatedImageName){
            unlink($rotatedImageName);
        }
    }

    private function addOverviewMap($ovData)
    {
        // calculate needed image size
        $quality = $this->data['quality'];
        $ovImageWidth = round($this->conf['overview']['width'] / 25.4 * $quality);
        $ovImageHeight = round($this->conf['overview']['height'] / 25.4 * $quality);
        $widthParam = '&WIDTH=' . $ovImageWidth;
        $heightParam = '&HEIGHT=' . $ovImageHeight;
        // gd pixel coords are top down!
        $ovPixelBox = new Box(0, $ovImageHeight, $ovImageWidth, 0);
        $centerx = $ovData['center']['x'];
        $centery = $ovData['center']['y'];
        $ovHeight = $ovData['height'];
        $ovWidth = $ovHeight * $this->conf['overview']['width'] / $this->conf['overview']['height'];
        $minX = $centerx - $ovWidth * 0.5;
        $minY = $centery - $ovHeight * 0.5;
        $maxX = $centerx + $ovWidth * 0.5;
        $maxY = $centery + $ovHeight * 0.5;
        $ovProjectedBox = new Box($minX, $minY, $maxX, $maxY);
        if (!empty($ovData['changeAxis'])) {
            $bbox = '&BBOX=' . $minY . ',' . $minX . ',' . $maxY . ',' . $maxX;
        } else {
            $bbox = '&BBOX=' . $minX . ',' . $minY . ',' . $maxX . ',' . $maxY;
        }

        $image = $this->makeBlank($ovImageWidth, $ovImageHeight);
        foreach ($ovData['layers'] as $i => $layerUrl) {
            $url = strstr($layerUrl, '&BBOX', true);
            $url .= $bbox . $widthParam . $heightParam;
            $layerImage = $this->downloadImage($url);
            if ($layerImage) {
                imagecopyresampled($image, $layerImage,
                    0, 0, 0, 0,
                    $ovImageWidth, $ovImageHeight,
                    imagesx($layerImage), imagesy($layerImage));
                imagedestroy($layerImage);
                unset($layerImage);
            } else {
                $this->getLogger()->warn("Failed overview request to {$url}");
            }
        }

        $ovTransform = Affine2DTransform::boxToBox($ovProjectedBox, $ovPixelBox);

        $points = array(
            $ovTransform->transformXy($this->data['extent_feature'][0]),
            $ovTransform->transformXy($this->data['extent_feature'][3]),
            $ovTransform->transformXy($this->data['extent_feature'][2]),
            $ovTransform->transformXy($this->data['extent_feature'][1]),
        );

        $red = imagecolorallocate($image,255,0,0);
        imageline ( $image, $points[0]['x'], $points[0]['y'], $points[1]['x'], $points[1]['y'], $red);
        imageline ( $image, $points[1]['x'], $points[1]['y'], $points[2]['x'], $points[2]['y'], $red);
        imageline ( $image, $points[2]['x'], $points[2]['y'], $points[3]['x'], $points[3]['y'], $red);
        imageline ( $image, $points[3]['x'], $points[3]['y'], $points[0]['x'], $points[0]['y'], $red);

        $tempPath = $this->makeTempFile('mb_print_temp_overview');
        imagepng($image, $tempPath);

        // add image to pdf
        $this->pdf->Image($tempPath,
                    $this->conf['overview']['x'],
                    $this->conf['overview']['y'],
                    $this->conf['overview']['width'],
                    $this->conf['overview']['height'],
                    'png');
        unlink($tempPath);

        // draw border rectangle
        $this->pdf->Rect($this->conf['overview']['x'],
                         $this->conf['overview']['y'],
                         $this->conf['overview']['width'],
                         $this->conf['overview']['height']);

    }

    private function addScaleBar()
    {
        $pdf = $this->pdf;
        $pdf->SetLineWidth(0.1);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFillColor(0,0,0);
        $pdf->SetFont('arial', '', 10 );

        $length = 0.01 * $this->data['scale_select'] * 5;
        $suffix = 'm';

        $pdf->Text( $this->conf['scalebar']['x'] -1 , $this->conf['scalebar']['y'] - 1 , '0' );
        $pdf->Text( $this->conf['scalebar']['x'] + 46, $this->conf['scalebar']['y'] - 1 , $length . '' . $suffix);

        $pdf->Rect($this->conf['scalebar']['x'], $this->conf['scalebar']['y'], 10, 2, 'FD');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($this->conf['scalebar']['x'] + 10 , $this->conf['scalebar']['y'], 10, 2, 'FD');
        $pdf->SetFillColor(0,0,0);
        $pdf->Rect($this->conf['scalebar']['x'] + 20  , $this->conf['scalebar']['y'], 10, 2, 'FD');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($this->conf['scalebar']['x'] + 30 , $this->conf['scalebar']['y'], 10, 2, 'FD');
        $pdf->SetFillColor(0,0,0);
        $pdf->Rect($this->conf['scalebar']['x'] + 40  , $this->conf['scalebar']['y'], 10, 2, 'FD');
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
        if (!$this->user || $this->user == 'anon.') {
            return;
        }

        $groups = $this->user->getGroups();
        $group = $groups[0];

        if(!isset($group)){
            return;
        }

        $dynImage = $this->resourceDir . '/images/' . $group->getTitle() . '.png';
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
        if (!$this->user || $this->user == 'anon.'){
            return;
        }

        $groups = $this->user->getGroups();
        $group = $groups[0];

        if(!isset($group)){
            return;
        }

        $this->pdf->SetFont('Arial', '', $this->conf['fields']['dynamic_text']['fontsize']);
        $this->pdf->MultiCell($this->conf['fields']['dynamic_text']['width'],
                $this->conf['fields']['dynamic_text']['height'],
                utf8_decode($group->getDescription()));

    }

    private function getFeature($schemaName, $featureId)
    {
        $featureTypeService = $this->container->get('features');
        $featureType = $featureTypeService->get($schemaName);
        $feature = $featureType->get($featureId);
        return $feature;
    }

    private function getResizeFactor()
    {
        if ($this->data['quality'] != 72) {
            return $this->data['quality'] / 72;
        } else {
            return 1;
        }
    }

    private function drawPolygon($geometry, $image)
    {
        $resizeFactor = $this->getResizeFactor();
        $style = $this->getStyle($geometry);
        foreach($geometry['coordinates'] as $ring) {
            if(count($ring) < 3) {
                continue;
            }

            $points = array();
            foreach($ring as $c) {
                $p = $this->featureTransform->transformPair($c);
                $points[] = floatval($p[0]);
                $points[] = floatval($p[1]);
            }
            imagesetthickness($image, 0);

            if($style['fillOpacity'] > 0){
                $color = $this->getColor(
                    $style['fillColor'],
                    $style['fillOpacity'],
                    $image);
                imagefilledpolygon($image, $points, count($ring), $color);
            }
            // Border
            if ($style['strokeWidth'] > 0) {
                $color = $this->getColor(
                    $style['strokeColor'],
                    $style['strokeOpacity'],
                    $image);
                imagesetthickness($image, $style['strokeWidth'] * $resizeFactor);
                imagepolygon($image, $points, count($ring), $color);
            }
        }
    }

    private function drawMultiPolygon($geometry, $image)
    {
        $resizeFactor = $this->getResizeFactor();
        $style = $this->getStyle($geometry);
        foreach($geometry['coordinates'] as $element) {
            foreach($element as $ring) {
                if(count($ring) < 3) {
                    continue;
                }

                $points = array();
                foreach($ring as $c) {
                    $p = $this->featureTransform->transformPair($c);
                    $points[] = floatval($p[0]);
                    $points[] = floatval($p[1]);
                }
                imagesetthickness($image, 0);
                // Filled area
                if($style['fillOpacity'] > 0){
                    $color = $this->getColor(
                        $style['fillColor'],
                        $style['fillOpacity'],
                        $image);
                    imagefilledpolygon($image, $points, count($ring), $color);
                }
                // Border
                if ($style['strokeWidth'] > 0) {
                    $color = $this->getColor(
                        $style['strokeColor'],
                        $style['strokeOpacity'],
                        $image);
                    imagesetthickness($image, $style['strokeWidth'] * $resizeFactor);
                    imagepolygon($image, $points, count($ring), $color);
                }
            }
        }
    }

    private function drawLineString($geometry, $image)
    {
        $resizeFactor = $this->getResizeFactor();
        $style = $this->getStyle($geometry);
        $color = $this->getColor(
            $style['strokeColor'],
            $style['strokeOpacity'],
            $image);
        if ($style['strokeWidth'] == 0) {
            return;
        }
        imagesetthickness($image, $style['strokeWidth'] * $resizeFactor);

        for($i = 1; $i < count($geometry['coordinates']); $i++) {
            $from = $this->featureTransform->transformPair($geometry['coordinates'][$i - 1]);
            $to = $this->featureTransform->transformPair($geometry['coordinates'][$i]);

            imageline($image, $from[0], $from[1], $to[0], $to[1], $color);
        }
    }

    private function drawMultiLineString($geometry, $image)
    {
        $resizeFactor = $this->getResizeFactor();
        $style = $this->getStyle($geometry);
        $color = $this->getColor(
            $style['strokeColor'],
            $style['strokeOpacity'],
            $image);
        if ($style['strokeWidth'] == 0) {
            return;
        }
        imagesetthickness($image, $style['strokeWidth'] * $resizeFactor);

        foreach($geometry['coordinates'] as $coords) {
            for($i = 1; $i < count($coords); $i++) {
                $from = $this->featureTransform->transformPair($coords[$i - 1]);
                $to = $this->featureTransform->transformPair($coords[$i]);
                imageline($image, $from[0], $from[1], $to[0], $to[1], $color);
            }
        }
    }

    private function drawPoint($geometry, $image)
    {
        $style = $this->getStyle($geometry);
        $resizeFactor = $this->getResizeFactor();

        $p = $this->featureTransform->transformPair($geometry['coordinates']);

        if(isset($style['label'])){
            // draw label with halo
            $color = $this->getColor($style['fontColor'], 1, $image);
            $bgcolor = $this->getColor($style['labelOutlineColor'], 1, $image);
            $fontPath = $this->resourceDir.'/fonts/';
            $font = $fontPath . 'OpenSans-Bold.ttf';

            $fontSize = 10 * $resizeFactor;
            imagettftext($image, $fontSize, 0, $p[0], $p[1]+$resizeFactor, $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, $fontSize, 0, $p[0], $p[1]-$resizeFactor, $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, $fontSize, 0, $p[0]-$resizeFactor, $p[1], $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, $fontSize, 0, $p[0]+$resizeFactor, $p[1], $bgcolor, $font, $geometry['style']['label']);
            imagettftext($image, $fontSize, 0, $p[0], $p[1], $color, $font, $style['label']);
        }

        $radius = $resizeFactor * $style['pointRadius'];
        // Filled circle
        if($style['fillOpacity'] > 0){
            $color = $this->getColor(
                $style['fillColor'],
                $style['fillOpacity'],
                $image);
            imagesetthickness($image, 0);
            imagefilledellipse($image, $p[0], $p[1], 2 * $radius, 2 * $radius, $color);
        }
        // Circle border
        if ($style['strokeWidth'] > 0 && $style['strokeOpacity'] > 0) {
            $color = $this->getColor(
                $style['strokeColor'],
                $style['strokeOpacity'],
                $image);
            imagesetthickness($image, $style['strokeWidth'] * $resizeFactor);
            imageellipse($image, $p[0], $p[1], 2 * $radius, 2 * $radius, $color);
        }
    }

    /**
     * Seemingly redundant override is necessary because the drawSomething methods are all private...
     *
     * @param resource $image
     * @param mixed[] $vectorLayers
     */
    protected function drawFeatures($image, $vectorLayers)
    {
        imagesavealpha($image, true);
        imagealphablending($image, true);

        foreach ($vectorLayers as $layer) {
            foreach ($layer['geometries'] as $geometry) {
                $renderMethodName = 'draw' . $geometry['type'];
                if (!method_exists($this, $renderMethodName)) {
                    continue;
                }
                $this->$renderMethodName($geometry, $image);
            }
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
          if(isset($this->conf['legendpage_image']) && $this->conf['legendpage_image']){
             $this->addLegendPageImage();
          }
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
                        if(isset($this->conf['legendpage_image']) && $this->conf['legendpage_image']){
                           $this->addLegendPageImage();
                        }
                        if($x + 20 > ($this->pdf->getWidth())){
                            $this->pdf->addPage('P');
                            $x = 5;
                            $y = 10;
                            if(isset($this->conf['legendpage_image']) && $this->conf['legendpage_image']){
                               $this->addLegendPageImage();
                            }
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
                                if(isset($this->conf['legendpage_image']) && $this->conf['legendpage_image']){ 
                                   $this->addLegendPageImage();
                                } 
                            }
                        }else if (($y-$yStartPosition) + $tempY + 10 > $height){
                                $this->pdf->addPage('P');
                                $x = 5;
                                $y = 10;
                                $legendConf = false;
                                if(isset($this->conf['legendpage_image']) && $this->conf['legendpage_image']){ 
                                   $this->addLegendPageImage();
                                } 
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
                            if (!empty($this->conf['legendpage_image'])) {
                               $this->addLegendPageImage();
                            }
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
                            if (!empty($this->conf['legendpage_image'])) {
                               $this->addLegendPageImage();
                            }
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


    private function addLegendPageImage()
    {

        $legendpageImage = $this->resourceDir . '/images/' . 'legendpage_image'. '.png';

        if (!$this->user || $this->user == 'anon.') {
            $legendpageImage = $this->resourceDir . '/images/' . 'legendpage_image'. '.png';
        }else{
          $groups = $this->user->getGroups();
          $group = $groups[0];

          if(isset($group)){
              $legendpageImage = $this->resourceDir . '/images/' . $group->getTitle() . '.png';
          }
        }

        if(file_exists ($legendpageImage)){
            $this->pdf->Image($legendpageImage,
                            $this->conf['legendpage_image']['x'],
                            $this->conf['legendpage_image']['y'],
                            0,
                            $this->conf['legendpage_image']['height'],
                            'png');
        }
    }

    /**
     * Get geometry style
     *
     * @param mixed[] $geometry
     * @return array
     */
    private function getStyle($geometry)
    {
        return array_merge($this->defaultStyle, $geometry['style']);
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
}
