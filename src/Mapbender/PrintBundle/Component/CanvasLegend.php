<?php

namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Component\ColorUtils;

class CanvasLegend
{

    private \GdImage $image;
    protected string $font = __DIR__ . '/../../../../../config/MapbenderPrintBundle/fonts/OpenSans-Regular.ttf';

    private int $offset = 15;
    protected int $legendWidth = 300;
    protected int $layerHeight = 25;
    protected int $symbolHeight = 15;
    protected int $symbolWidth = 35;
    protected int $layerTitleFontSize = 12;
    protected float $layerTitleLineHeight = 1.5;
    protected string $layerLabelString = "Label";

    protected array $imageCache = [];

    public function __construct(private array $layers)
    {
        $this->image = imagecreatetruecolor($this->legendWidth, $this->offset + $this->layerHeight * count($this->layers) * 4);
        $this->prepareCanvas();

        foreach ($this->layers as $layer) {
            $this->addSubLayer($layer['title'] ?? "", $layer['canvas'] ?? $layer['style']);
        }

        $existingCanvas = $this->image;
        $this->image = imagecrop($existingCanvas, ['x' => 0, 'y' => 0, 'width' => imagesx($existingCanvas), 'height' => $this->offset]);
        imagedestroy($existingCanvas);
    }

    function getImage(): \GdImage
    {
        return $this->image;
    }

    protected function addSubLayer(string $label, array|string $style): void
    {
        if (is_array($style)) {
            $this->populateCanvas($style);
        } else {
            $this->copyExistingCanvas($style);
        }


        $textBox = imagettfbbox($this->layerTitleFontSize, 0, $this->font, $label);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textY = ($this->symbolHeight + $textHeight) / 2;
        $black = imagecolorallocate($this->image, 0, 0, 0);

        $this->drawMultilineText($textY, $black, $label);
        $this->offset += $this->symbolHeight - $this->layerTitleFontSize * $this->layerTitleLineHeight + 10;
    }

    protected function populateCanvas(array $style): void
    {
        if (isset($style['image'])) {
            $this->drawExternalImage($style);
            return;
        }

        $circle = isset($style['circle']) && $style['circle'];
        $circleRadius = $circle ? ($style['circleRadius'] ?? 5) : 0;

        if (isset($style['fillColor'])) {
            $fillColor = ColorUtils::parseColorToRgb($style['fillColor']);
            $fillOpacity = $style['fillOpacity'] ?? 1;
            $fill = imagecolorallocatealpha($this->image, $fillColor['red'], $fillColor['green'], $fillColor['blue'], 127 * (1 - $fillOpacity));
            if ($circle) {
                $cx = (int)($this->symbolWidth / 2);
                $cy = (int)($this->offset + $this->symbolHeight / 2);
                imagefilledellipse($this->image, $cx, $cy, $circleRadius * 2, $circleRadius * 2, $fill);
            } else {
                imagefilledrectangle($this->image, 0, $this->offset, $this->symbolWidth, $this->offset + $this->symbolHeight, $fill);
            }
        }

        if (isset($style['strokeColor']) && ($style['strokeWidth'] ?? 0) > 0) {
            $strokeColor = ColorUtils::parseColorToRgb($style['strokeColor']);
            $strokeOpacity = $style['strokeOpacity'] ?? 1;
            $stroke = imagecolorallocatealpha($this->image, $strokeColor['red'], $strokeColor['green'], $strokeColor['blue'], 127 * (1 - $strokeOpacity));
            imagesetthickness($this->image, $style['strokeWidth']);
            if ($circle) {
                $cx = (int)($this->symbolWidth / 2);
                $cy = (int)($this->offset + $this->symbolHeight / 2);
                imageellipse($this->image, $cx, $cy, $circleRadius * 2, $circleRadius * 2, $stroke);
            } elseif (!isset($style['fillColor'])) {
                // draw a centered line if no fill color is set
                $lineY = (int)($this->offset + $this->symbolHeight / 2);
                imageline($this->image, 0, $lineY, $this->symbolWidth - 1, $lineY, $stroke);
            } else {
                imagerectangle($this->image, 0, $this->offset, $this->symbolWidth - 1, $this->offset + $this->symbolHeight - 1, $stroke);
            }
        }

        if (array_key_exists('label', $style) && $style['label']) {
            $this->drawLabelText($style);
        }
    }

    protected function copyExistingCanvas(string $dataUri): void
    {
        $base64String = explode(',', $dataUri)[1];
        $imageData = base64_decode($base64String);
        $canvas = imagecreatefromstring($imageData);
        if (!$canvas) return;

        imagecopy($this->image, $canvas, 0, $this->offset, 0, 0, imagesx($canvas), imagesy($canvas));
        imagedestroy($canvas);
    }

    public function prepareCanvas(): void
    {
        $white = imagecolorallocate($this->image, 255, 255, 255);
        imagefill($this->image, 0, 0, $white);
    }

    public function drawLabelText(array $style): void
    {
        $fontColor = ColorUtils::parseColorToRgb($style['fontColor']);
        $fontSize = min(10, (int)$style['fontSize']);
        $textColor = imagecolorallocate($this->image, $fontColor['red'], $fontColor['green'], $fontColor['blue']);


        $textBox = imagettfbbox($fontSize, 0, $this->font, $this->layerLabelString);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX = ($this->symbolWidth - $textWidth) / 2;
        $textY = ($this->symbolHeight + $textHeight) / 2;

        // Draw label outline if needed
        if (isset($style['labelOutlineWidth']) && $style['labelOutlineWidth'] > 0) {
            $labelOutlineColor = ColorUtils::parseColorToRgb($style['labelOutlineColor']);
            $outlineColor = imagecolorallocate($this->image, $labelOutlineColor['red'], $labelOutlineColor['green'], $labelOutlineColor['blue']);
            $outlineWidth = $style['labelOutlineWidth'];
            for ($x = -$outlineWidth; $x <= $outlineWidth; $x++) {
                for ($y = -$outlineWidth; $y <= $outlineWidth; $y++) {
                    imagettftext($this->image, $fontSize, 0, $textX + $x, $this->offset + $textY + $y, $outlineColor, $this->font, $this->layerLabelString);
                }
            }
        }

        // Draw the label
        imagettftext($this->image, $fontSize, 0, $textX, $textY + $this->offset, $textColor, $this->font, "Label");
    }

    public function drawMultilineText(float|int $textY, bool|int $black, string $label): void
    {
        $words = explode(" ", $label);

        $line = '';
        $currentWord = 0;

        while ($currentWord < count($words)) {
            $dimensions = imagettfbbox($this->layerTitleFontSize, 0, $this->font, $line . $words[$currentWord]);
            $lineWidth = $dimensions[2] - $dimensions[0];

            if ($lineWidth > $this->legendWidth - $this->symbolWidth - 10) {
                imagettftext($this->image, $this->layerTitleFontSize, 0, $this->symbolWidth + 10, $textY + $this->offset, $black, $this->font, $line);
                $this->offset += $this->layerTitleFontSize * $this->layerTitleLineHeight;
                $line = $words[$currentWord] . ' ';
            } else {
                $line .= $words[$currentWord] . ' ';
            }
            $currentWord++;
        }

        if ($line) {
            imagettftext($this->image, $this->layerTitleFontSize, 0, $this->symbolWidth + 10, $textY + $this->offset, $black, $this->font, $line);
            $this->offset += $this->layerTitleFontSize * 1.3;
        }

    }

    private function drawExternalImage(array $style)
    {
        if (!isset($this->imageCache[$style['image']])) {
            $this->imageCache[$style['image']] = @file_get_contents($style['image']);
        }
        $externalImage = @imagecreatefromstring($this->imageCache[$style['image']]);
        if (!$externalImage) {
            return;
        }

        $srcW = imagesx($externalImage);
        $srcH = imagesy($externalImage);

        // Sprite Parameters
        $sx = isset($style['imageX']) ? (int)$style['imageX'] : 0;
        $sy = isset($style['imageY']) ? (int)$style['imageY'] : 0;
        $sw = isset($style['imageWidth']) ? (int)$style['imageWidth'] : $srcW;
        $sh = isset($style['imageHeight']) ? (int)$style['imageHeight'] : $srcH;

        // Scale down to target size while maintaining aspect ratio
        $scale = min(1, $this->symbolWidth / $sw, $this->symbolHeight / $sh);
        $dw = (int)round($sw * $scale);
        $dh = (int)round($sh * $scale);

        // center the image in the canvas
        $dx = (int)(($this->symbolWidth - $dw) / 2);
        $dy = (int)($this->offset + ($this->symbolHeight - $dh) / 2);

        imagecopyresampled(
            $this->image,
            $externalImage,
            $dx,
            $dy,
            $sx,
            $sy,
            $dw,
            $dh,
            $sw,
            $sh
        );
        imagedestroy($externalImage);
    }

}
