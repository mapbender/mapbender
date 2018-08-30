<?php
namespace Mapbender\PrintBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\CommonProxy;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Mapbender3 Print Service.
 *
 * @author Stefan Winkelmann
 */
class PrintService extends ImageExportService
{
    /** @var PDF_ImageAlpha */
    protected $pdf;
    protected $conf;
    protected $rotation;
    protected $resourceDir;
    protected $finalImageName;
    protected $user;
    protected $imageWidth;
    protected $imageHeight;
    protected $neededExtentWidth;
    protected $neededExtentHeight;
    protected $neededImageWidth;
    protected $neededImageHeight;

    /**
     * @var array Default geometry style
     */
    protected $defaultStyle = array(
        "strokeWidth" => 1
    );

    public function doPrint($data)
    {
        $this->setup($data);

        if ($data['rotation'] == 0) {
            $this->createFinalMapImage();
        } else {
            $this->createFinalRotatedMapImage();
        }

        return $this->buildPdf();
    }

    private function setup($data)
    {
        // resource dir
        $this->resourceDir = $this->container->getParameter('kernel.root_dir') . '/Resources/MapbenderPrintBundle';

        $this->user      = $this->getUser();

        // data from client
        $this->data = $data;

        // template configuration from odg
        $odgParser = new OdgParser($this->container);
        $this->conf = $conf = $odgParser->getConf($data['template']);

        // image size
        $this->imageWidth = round($conf['map']['width'] / 25.4 * $data['quality']);
        $this->imageHeight = round($conf['map']['height'] / 25.4 * $data['quality']);

        // map requests array
        $this->mapRequests = array();
        foreach ($data['layers'] as $i => $layer) {
            if ($layer['type'] != 'wms') {
                continue;
            }
            $request = strstr($layer['url'], '&BBOX', true);

            $extentWidth = $data['extent']['width'];
            $extentHeight = $data['extent']['height'];
            $centerx = $data['center']['x'];
            $centery = $data['center']['y'];

            // switch axis for some EPSG if WMS Version 1.3.0
            if (!empty($layer['changeAxis'])){
                $extentWidth = $data['extent']['height'];
                $extentHeight = $data['extent']['width'];
                $centerx = $data['center']['y'];
                $centery = $data['center']['x'];
            }

            // switch if image is rotated
            $this->rotation = $rotation = $data['rotation'];
            if ($rotation == 0) {
                // calculate needed bbox
                $minX = $centerx - $extentWidth * 0.5;
                $minY = $centery - $extentHeight * 0.5;
                $maxX = $centerx + $extentWidth * 0.5;
                $maxY = $centery + $extentHeight * 0.5;

                $width = '&WIDTH=' . $this->imageWidth;
                $height =  '&HEIGHT=' . $this->imageHeight;
            }else{
                // calculate needed bbox
                $neededExtentWidth = abs(sin(deg2rad($rotation)) * $extentHeight) +
                    abs(cos(deg2rad($rotation)) * $extentWidth);
                $neededExtentHeight = abs(sin(deg2rad($rotation)) * $extentWidth) +
                    abs(cos(deg2rad($rotation)) * $extentHeight);

                $this->neededExtentWidth = $neededExtentWidth;
                $this->neededExtentHeight = $neededExtentHeight;

                $minX = $centerx - $neededExtentWidth * 0.5;
                $minY = $centery - $neededExtentHeight * 0.5;
                $maxX = $centerx + $neededExtentWidth * 0.5;
                $maxY = $centery + $neededExtentHeight * 0.5;

                // calculate needed image size
                $neededImageWidth = round(abs(sin(deg2rad($rotation)) * $this->imageHeight) +
                    abs(cos(deg2rad($rotation)) * $this->imageWidth));
                $neededImageHeight = round(abs(sin(deg2rad($rotation)) * $this->imageWidth) +
                    abs(cos(deg2rad($rotation)) * $this->imageHeight));

                $this->neededImageWidth = $neededImageWidth;
                $this->neededImageHeight = $neededImageHeight;

                $width = '&WIDTH=' . $neededImageWidth;
                $height =  '&HEIGHT=' . $neededImageHeight;
            }

            $request .= '&BBOX=' . $minX . ',' . $minY . ',' . $maxX . ',' . $maxY;
            $request .= $width . $height;

            if(!isset($this->data['replace_pattern'])){
                if ($this->data['quality'] != '72') {
                    $request .= '&map_resolution=' . $this->data['quality'];
                }
            }

            $this->mapRequests[$i] = $request;
        }

        if(isset($this->data['replace_pattern'])){
            $this->addReplacePattern();
        }

    }

    private function addReplacePattern()
    {
        $quality = $this->data['quality'];
        $default = '';
        foreach ($this->mapRequests as $k => $url) {
            foreach ($this->data['replace_pattern'] as $rKey => $pattern) {
                if(isset($pattern['default'])){
                    if(isset($pattern['default'][$quality])){
                        $default = $pattern['default'][$quality];
                    }
                    continue;
                }
                if(strpos($url,$pattern['pattern']) === false){
                    continue;
                }
                if(strpos($url,$pattern['pattern']) !== false){
                    if(isset($pattern['replacement'][$quality])){
                        $url = str_replace($pattern['pattern'], $pattern['replacement'][$quality], $url);
                        $signer = $this->container->get('signer');
                        $this->mapRequests[$k] = $signer->signUrl($url);
                        continue 2;
                    }
                }

            }
            $url .= $default;
            $this->mapRequests[$k] = $url;
        }
    }

    private function createFinalMapImage()
    {
        $width = $this->imageWidth;
        $height = $this->imageHeight;
        $imageNames = $this->getImages($width, $height);

        // create final merged image
        $this->finalImageName = $this->makeTempFile('mb_print_final');
        $finalImage = imagecreatetruecolor($width, $height);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $width, $height, $bg);

        foreach ($imageNames as $imageName) {
            // Note: suppressing the errors IS bad, bad PHP wants us to do it that way
                $src = imagecreatefrompng($imageName);
            // Check that imagecreatefrompng did yield something
            if ($src) {
                $dest = $finalImage;
                imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);
                imagepng($dest, $this->finalImageName);
                unlink($imageName);
            }
        }
        //draw features
        $this->drawFeatures();
    }

    private function createFinalRotatedMapImage()
    {
        $rotation = $this->rotation;
        $neededImageWidth = $this->neededImageWidth;
        $neededImageHeight = $this->neededImageHeight;
        $imageWidth = $this->imageWidth;
        $imageHeight = $this->imageHeight;

        $imageNames = $this->getImages($neededImageWidth,$neededImageHeight);

        // create temp merged image
        $tempImageName = $this->makeTempFile('mb_print_temp');
        $tempImage = imagecreatetruecolor($neededImageWidth, $neededImageHeight);
        $bg = ImageColorAllocate($tempImage, 255, 255, 255);
        imagefilledrectangle($tempImage, 0, 0, $neededImageWidth, $neededImageHeight, $bg);
        imagepng($tempImage, $tempImageName);

        foreach ($imageNames as $imageName) {
            // Note: suppressing the errors IS bad, bad PHP wants us to do it that way
            $src = imagecreatefrompng($imageName);
            // Check that imagecreatefrompng did yield something
            if ($src) {
                $dest = imagecreatefrompng($tempImageName);
                imagecopy($dest, $src, 0, 0, 0, 0, $neededImageWidth,
                    $neededImageHeight);
                imagepng($dest, $tempImageName);
                unlink($imageName);
            }
        }

        // draw features
        $this->finalImageName = $tempImageName;
        $this->drawFeatures();

        // rotate temp image
        $tempImage2 = imagecreatefrompng($tempImageName);
        $transColor = imagecolorallocatealpha($tempImage2, 255, 255, 255, 127);
        $rotatedImage = imagerotate($tempImage2, $rotation, $transColor);
        imagealphablending($rotatedImage, false);
        imagesavealpha($rotatedImage, true);
        $rotatedImageName = $this->makeTempFile('mb_print_rotated');
        imagepng($rotatedImage, $rotatedImageName);
        unlink($tempImageName);
        unlink($rotatedImageName);

        // clip final image from rotated
        $rotatedWidth = round(abs(sin(deg2rad($rotation)) * $neededImageHeight) +
            abs(cos(deg2rad($rotation)) * $neededImageWidth));
        $rotatedHeight = round(abs(sin(deg2rad($rotation)) * $neededImageWidth) +
            abs(cos(deg2rad($rotation)) * $neededImageHeight));
        $newx = ($rotatedWidth - $imageWidth ) / 2;
        $newy = ($rotatedHeight - $imageHeight ) / 2;

        $clippedImage = imagecreatetruecolor($imageWidth, $imageHeight);
        imagealphablending($clippedImage, false);
        imagesavealpha($clippedImage, true);
        imagecopy($clippedImage, $rotatedImage, 0, 0, $newx, $newy,
            $imageWidth, $imageHeight);

        $this->finalImageName = $this->makeTempFile('mb_print_final');
        imagepng($clippedImage, $this->finalImageName);
    }

    private function getImages($width, $height)
    {
        $logger        = $this->getLogger();
        $imageNames    = array();
        foreach ($this->mapRequests as $i => $url) {
            $this->getLogger()->debug("Print Request Nr.: " . $i . ' ' . $url);

            $mapRequestResponse = $this->mapRequest($url);

            $imageName    = $this->makeTempFile('mb_print');
            $imageNames[] = $imageName;
            $rawImage = $this->serviceResponseToGdImage($imageName, $mapRequestResponse);

            if (!$rawImage) {
                $logger->warning("Print request produced no valid image response, skipping layer", array(
                    'url' => $url,
                ));
                $logger->debug($mapRequestResponse->getContent());
                continue;
            }

            if ($rawImage !== null) {
                $this->forceToRgba($imageName, $rawImage, $this->data['layers'][$i]['opacity']);
            }
        }
        return $imageNames;
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

        $this->pdf = $pdf = new PDF_Extensions();

        $template = $this->data['template'];
        $pdfFile = $this->resourceDir . '/templates/' . $template . '.pdf';
        $pageCount = $pdf->setSourceFile($pdfFile);
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
            $this->addOverviewMap();
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

    private function addOverviewMap()
    {
        // calculate needed image size
        $quality = $this->data['quality'];
        $ovImageWidth = round($this->conf['overview']['width'] / 25.4 * $quality);
        $ovImageHeight = round($this->conf['overview']['height'] / 25.4 * $quality);
        $width = '&WIDTH=' . $ovImageWidth;
        $height = '&HEIGHT=' . $ovImageHeight;

        $changeAxis = false;

        // get images
        $tempNames = array();
        $logger = $this->getLogger();
        foreach ($this->data['overview'] as $i => $layer) {
            // calculate needed bbox
            $ovWidth = $this->conf['overview']['width'] * $layer['scale'] / 1000;
            $ovHeight = $this->conf['overview']['height'] * $layer['scale'] / 1000;
            $centerx = $this->data['center']['x'];
            $centery = $this->data['center']['y'];

            if (!empty($layer['changeAxis'])) {
                $ovWidth = $this->conf['overview']['height'] * $layer['scale'] / 1000;
                $ovHeight = $this->conf['overview']['width'] * $layer['scale'] / 1000;
                $centerx = $this->data['center']['y'];
                $centery = $this->data['center']['x'];
                $changeAxis = true;
            }

            $minX = $centerx - $ovWidth * 0.5;
            $minY = $centery - $ovHeight * 0.5;
            $maxX = $centerx + $ovWidth * 0.5;
            $maxY = $centery + $ovHeight * 0.5;
            $bbox = '&BBOX=' . $minX . ',' . $minY . ',' . $maxX . ',' . $maxY;

            $url = strstr($layer['url'], '&BBOX', true);
            $url .= $bbox . $width . $height;

            $logger->debug("Print Overview Request Nr.: " . $i . ' ' . $url);

            $overviewRequestResponse = $this->mapRequest($url);
            $imageName = $this->makeTempFile('mb_print');
            $tempNames[] = $imageName;
            $im = $this->serviceResponseToGdImage($imageName, $overviewRequestResponse);

            if ($im !== null) {
                imagesavealpha($im, true);
                imagepng($im, $imageName);
            }
        }

        // create final merged image
        $finalImageName = $this->makeTempFile('mb_print_merged');
        $finalImage = imagecreatetruecolor($ovImageWidth, $ovImageHeight);
        $bg = ImageColorAllocate($finalImage, 255, 255, 255);
        imagefilledrectangle($finalImage, 0, 0, $ovImageWidth,
            $ovImageHeight, $bg);
        imagepng($finalImage, $finalImageName);
        foreach ($tempNames as $tempName) {
            // Note: suppressing the errors IS bad, bad PHP wants us to do it that way
            $src = imagecreatefrompng($tempName);
            // Check that imagecreatefrompng did yield something
            if ($src) {
                $dest = imagecreatefrompng($finalImageName);
                $src = imagecreatefrompng($tempName);
                imagecopy($dest, $src, 0, 0, 0, 0, $ovImageWidth,
                    $ovImageHeight);
                imagepng($dest, $finalImageName);
            }
            unlink($tempName);
        }

        $image = imagecreatefrompng($finalImageName);

        // add red extent rectangle
        if (!$changeAxis) {
            $ll_x = $this->data['extent_feature'][3]['x'];
            $ll_y = $this->data['extent_feature'][3]['y'];
            $ul_x = $this->data['extent_feature'][0]['x'];
            $ul_y = $this->data['extent_feature'][0]['y'];

            $lr_x = $this->data['extent_feature'][2]['x'];
            $lr_y = $this->data['extent_feature'][2]['y'];
            $ur_x = $this->data['extent_feature'][1]['x'];
            $ur_y = $this->data['extent_feature'][1]['y'];

            $p1 = $this->realWorld2ovMapPos($ovWidth, $ovHeight, $ll_x, $ll_y);
            $p2 = $this->realWorld2ovMapPos($ovWidth, $ovHeight, $ul_x, $ul_y);
            $p3 = $this->realWorld2ovMapPos($ovWidth, $ovHeight, $ur_x, $ur_y);
            $p4 = $this->realWorld2ovMapPos($ovWidth, $ovHeight, $lr_x, $lr_y);
        } else {
            $ul_x = $this->data['extent_feature'][3]['x'];
            $ul_y = $this->data['extent_feature'][3]['y'];
            $ll_x = $this->data['extent_feature'][0]['x'];
            $ll_y = $this->data['extent_feature'][0]['y'];

            $ur_x = $this->data['extent_feature'][2]['x'];
            $ur_y = $this->data['extent_feature'][2]['y'];
            $lr_x = $this->data['extent_feature'][1]['x'];
            $lr_y = $this->data['extent_feature'][1]['y'];

            $p1 = $this->realWorld2ovMapPos($ovHeight, $ovWidth, $ll_x, $ll_y);
            $p2 = $this->realWorld2ovMapPos($ovHeight, $ovWidth, $ul_x, $ul_y);
            $p3 = $this->realWorld2ovMapPos($ovHeight, $ovWidth, $ur_x, $ur_y);
            $p4 = $this->realWorld2ovMapPos($ovHeight, $ovWidth, $lr_x, $lr_y);
        }

        $red = ImageColorAllocate($image,255,0,0);
        imageline ( $image, $p1[0], $p1[1], $p2[0], $p2[1], $red);
        imageline ( $image, $p2[0], $p2[1], $p3[0], $p3[1], $red);
        imageline ( $image, $p3[0], $p3[1], $p4[0], $p4[1], $red);
        imageline ( $image, $p4[0], $p4[1], $p1[0], $p1[1], $red);

        imagepng($image, $finalImageName);

        // add image to pdf
        $this->pdf->Image($finalImageName,
                    $this->conf['overview']['x'],
                    $this->conf['overview']['y'],
                    $this->conf['overview']['width'],
                    $this->conf['overview']['height'],
                    'png');
        // draw border rectangle
        $this->pdf->Rect($this->conf['overview']['x'],
                         $this->conf['overview']['y'],
                         $this->conf['overview']['width'],
                         $this->conf['overview']['height']);

        unlink($finalImageName);
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

    private function getColor($color, $alpha, $image)
    {
        list($r, $g, $b) = CSSColorParser::parse($color);

        if(0 == $alpha) {
            return ImageColorAllocate($image, $r, $g, $b);
        } else {
            $a = (1 - $alpha) * 127.0;
            return imagecolorallocatealpha($image, $r, $g, $b, $a);
        }
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
                if($this->rotation == 0){
                    $p = $this->realWorld2mapPos($c[0], $c[1]);
                }else{
                    $p = $this->realWorld2rotatedMapPos($c[0], $c[1]);
                }
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
                    if($this->rotation == 0){
                        $p = $this->realWorld2mapPos($c[0], $c[1]);
                    }else{
                        $p = $this->realWorld2rotatedMapPos($c[0], $c[1]);
                    }
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

            if($this->rotation == 0){
                $from = $this->realWorld2mapPos(
                    $geometry['coordinates'][$i - 1][0],
                    $geometry['coordinates'][$i - 1][1]);
                $to = $this->realWorld2mapPos(
                    $geometry['coordinates'][$i][0],
                    $geometry['coordinates'][$i][1]);
            }else{
                $from = $this->realWorld2rotatedMapPos(
                    $geometry['coordinates'][$i - 1][0],
                    $geometry['coordinates'][$i - 1][1]);
                $to = $this->realWorld2rotatedMapPos(
                    $geometry['coordinates'][$i][0],
                    $geometry['coordinates'][$i][1]);
            }

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
                if($this->rotation == 0){
                        $from = $this->realWorld2mapPos(
                                $coords[$i - 1][0],
                                $coords[$i - 1][1]);
                        $to = $this->realWorld2mapPos(
                                $coords[$i][0],
                                $coords[$i][1]);
                }else{
                        $from = $this->realWorld2rotatedMapPos(
                                $coords[$i - 1][0],
                                $coords[$i - 1][1]);
                        $to = $this->realWorld2rotatedMapPos(
                                $coords[$i][0],
                                $coords[$i][1]);
                }
                imageline($image, $from[0], $from[1], $to[0], $to[1], $color);
            }
        }
    }

    private function drawPoint($geometry, $image)
    {
        $style = $this->getStyle($geometry);
        $c = $geometry['coordinates'];
        $resizeFactor = $this->getResizeFactor();

        if($this->rotation == 0){
            $p = $this->realWorld2mapPos($c[0], $c[1]);
        }else{
            $p = $this->realWorld2rotatedMapPos($c[0], $c[1]);
        }

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

    private function drawFeatures()
    {
        $image = imagecreatefrompng($this->finalImageName);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        foreach ($this->data['layers'] as $idx => $layer) {
            if ('GeoJSON+Style' !== $layer['type']) {
                continue;
            }

            foreach ($layer['geometries'] as $geometry) {
                $renderMethodName = 'draw' . $geometry['type'];
                if (!method_exists($this, $renderMethodName)) {
                    continue;
                    //throw new \RuntimeException('Can not draw geometries of type "' . $geometry['type'] . '".');
                }
                $this->$renderMethodName($geometry, $image);
            }
        }
        imagepng($image, $this->finalImageName);
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
                if (false === @imagecreatefromstring(@file_get_contents($image))) {
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
                    $this->pdf->setXY($x,$y);
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
                      $this->pdf->setXY($x,$y);
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

        $url      = urldecode($url);
        $parsed   = parse_url($url);
        $host = isset($parsed['host']) ? $parsed['host'] : $this->container->get('request')->getHttpHost();
        $hostpath = $host . $parsed['path'];
        $pos      = strpos($hostpath, $this->urlHostPath);
        if ($pos === 0 && ($routeStr = substr($hostpath, strlen($this->urlHostPath))) !== false) {
            $attributes = $this->container->get('router')->match($routeStr);
            $gets       = array();
            parse_str($parsed['query'], $gets);
            $subRequest = new Request($gets, array(), $attributes, array(), array(), array(), '');
            $response   = $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
            $imagename  = $this->makeTempFile('mb_printlegend');
            file_put_contents($imagename, $response->getContent());
        } else {
            $proxy_config    = $this->container->getParameter("owsproxy.proxy");
            $proxy_query     = ProxyQuery::createFromUrl($url);
            $proxy           = new CommonProxy($proxy_config, $proxy_query);
            $browserResponse = $proxy->handle();

            $imagename = $this->makeTempFile('mb_printlegend');
            file_put_contents($imagename, $browserResponse->getContent());
        }

        return $imagename;
    }



    private function realWorld2mapPos($rw_x, $rw_y)
    {
        $quality   = $this->data['quality'];
        $mapWidth  = $this->data['extent']['width'];
        $mapHeight = $this->data['extent']['height'];
        $centerx   = $this->data['center']['x'];
        $centery   = $this->data['center']['y'];
        $minX      = $centerx - $mapWidth * 0.5;
        $minY      = $centery - $mapHeight * 0.5;
        $maxX      = $centerx + $mapWidth * 0.5;
        $maxY      = $centery + $mapHeight * 0.5;
        $extentx   = $maxX - $minX;
        $extenty   = $maxY - $minY;
        $pixPos_x  = (($rw_x - $minX) / $extentx) * round($this->conf['map']['width'] / 25.4 * $quality);
        $pixPos_y  = (($maxY - $rw_y) / $extenty) * round($this->conf['map']['height'] / 25.4 * $quality);

        return array($pixPos_x, $pixPos_y);
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

    private function realWorld2ovMapPos($ovWidth, $ovHeight, $rw_x, $rw_y)
    {
        $quality  = $this->data['quality'];
        $centerx  = $this->data['center']['x'];
        $centery  = $this->data['center']['y'];
        $minX     = $centerx - $ovWidth * 0.5;
        $minY     = $centery - $ovHeight * 0.5;
        $maxX     = $centerx + $ovWidth * 0.5;
        $maxY     = $centery + $ovHeight * 0.5;
        $extentx  = $maxX - $minX;
        $extenty  = $maxY - $minY;
        $pixPos_x = (($rw_x - $minX) / $extentx) * round($this->conf['overview']['width'] / 25.4 * $quality);
        $pixPos_y = (($maxY - $rw_y) / $extenty) * round($this->conf['overview']['height'] / 25.4 * $quality);

        return array($pixPos_x, $pixPos_y);
    }

    private function realWorld2rotatedMapPos($rw_x, $rw_y)
    {
        $centerx  = $this->data['center']['x'];
        $centery  = $this->data['center']['y'];
        $minX     = $centerx - $this->neededExtentWidth * 0.5;
        $minY     = $centery - $this->neededExtentHeight * 0.5;
        $maxX     = $centerx + $this->neededExtentWidth * 0.5;
        $maxY     = $centery + $this->neededExtentHeight * 0.5;
        $extentx  = $maxX - $minX;
        $extenty  = $maxY - $minY;
        $pixPos_x = (($rw_x - $minX) / $extentx) * $this->neededImageWidth;
        $pixPos_y = (($maxY - $rw_y) / $extenty) * $this->neededImageHeight;

        return array($pixPos_x, $pixPos_y);
    }

    /**
     * Get geometry style
     *
     * @param string $geometry Geometry
     * @return array Style
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

    /**
     * @return mixed|null
     */
    protected function getUser()
    {
        /** @var TokenStorage $tokenStorage */
        $tokenStorage = $this->container->get('security.token_storage');
        $token = $tokenStorage->getToken();
        if ($token) {
            return $token->getUser();
        } else {
            return null;
        }
    }
}
