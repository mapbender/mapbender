<?php

namespace Mapbender\PrintBundle\Component;

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

    public function __construct(private array $layers)
    {
        $this->image = imagecreatetruecolor($this->legendWidth, $this->offset + $this->layerHeight * count($this->layers) * 2);
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
        $fillColor = $this->hexToRgb($style['fillColor']);
        $fillOpacity = $style['fillOpacity'];
        $fill = imagecolorallocatealpha($this->image, $fillColor['red'], $fillColor['green'], $fillColor['blue'], 127 * (1 - $fillOpacity));
        imagefilledrectangle($this->image, 0, $this->offset, $this->symbolWidth, $this->offset + $this->symbolHeight, $fill);

        // Set stroke color
        if (isset($style['strokeColor']) && $style['strokeWidth'] > 0) {
            $strokeColor = $this->hexToRgb($style['strokeColor']);
            $strokeOpacity = $style['strokeOpacity'];
            $stroke = imagecolorallocatealpha($this->image, $strokeColor['red'], $strokeColor['green'], $strokeColor['blue'], 127 * (1 - $strokeOpacity));
            imagesetthickness($this->image, $style['strokeWidth']);
            imagerectangle($this->image, 0, $this->offset, $this->symbolWidth - 1, $this->offset + $this->symbolHeight - 1, $stroke);
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

    private function hexToRgb($hex)
    {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return ['red' => $r, 'green' => $g, 'blue' => $b];
    }

    public function prepareCanvas(): void
    {
        $white = imagecolorallocate($this->image, 255, 255, 255);
        imagefill($this->image, 0, 0, $white);
    }

    public function drawLabelText(array $style): void
    {
        $fontColor = $this->hexToRgb($style['fontColor']);
        $fontSize = min(10, (int)$style['fontSize']);
        $textColor = imagecolorallocate($this->image, $fontColor['red'], $fontColor['green'], $fontColor['blue']);


        $textBox = imagettfbbox($fontSize, 0, $this->font, $this->layerLabelString);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX = ($this->symbolWidth - $textWidth) / 2;
        $textY = ($this->symbolHeight + $textHeight) / 2;

        // Draw label outline if needed
        if (isset($style['labelOutlineWidth']) && $style['labelOutlineWidth'] > 0) {
            $labelOutlineColor = $this->hexToRgb($style['labelOutlineColor']);
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

        while($currentWord < count($words)) {
            $dimensions = imagettfbbox($this->layerTitleFontSize, 0, $this->font, $line . $words[$currentWord]);
            $lineWidth = $dimensions[2] - $dimensions[0];

            if ($lineWidth > $this->legendWidth - $this->symbolWidth - 10) {
                imagettftext($this->image, $this->layerTitleFontSize, 0, $this->symbolWidth + 10, $textY + $this->offset, $black, $this->font, $line);
                $this->offset += $this->layerTitleFontSize * $this->layerTitleLineHeight;
                $line = '';
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

}
