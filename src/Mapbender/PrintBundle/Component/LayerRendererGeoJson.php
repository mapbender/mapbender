<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;

/**
 * Renders "GeoJSON+Style" layers in image export and print.
 * These are technically not valid GeoJSON because they use a
 * non-conformant "style" entry inside features.
 */
class LayerRendererGeoJson extends LayerRenderer
{
    /** @var string */
    protected $fontPath;

    /**
     * @param string $fontPath
     */
    public function __construct($fontPath)
    {
        $this->fontPath = rtrim($fontPath, '/');
    }

    public function squashLayerDefinitions($layerDef, $nextLayerDef)
    {
        // @TODO: merge everything
        return false;
    }

    public function addLayer(ExportCanvas $canvas, $layerDef, Box $extent)
    {
        imagesavealpha($canvas->resource, true);
        imagealphablending($canvas->resource, true);

        if (empty($layerDef['features']) && array_key_exists('geometries', $layerDef)) {
            // legacy format support: rename non-conformant 'geometries' to conformant 'features'
            $layerDef['features'] = $layerDef['geometries'];
        }
        foreach ($layerDef['features'] as $feature) {
            $this->drawFeature($canvas, $feature);
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param array $feature GeoJSONish
     */
    public function drawFeature(ExportCanvas $canvas, $feature)
    {
        $type = $feature['type'];
        switch (strtolower($type)) {
            case 'point':
                $this->drawPoint($canvas, $feature);
                break;
            case 'linestring':
                $this->drawLineString($canvas, $feature);
                break;
            case 'polygon':
                $this->drawPolygon($canvas, $feature);
                break;
            case 'multipolygon':
                $this->drawMultiPolygon($canvas, $feature);
                break;
            case 'multilinestring':
                $this->drawMultiLineString($canvas, $feature);
                break;
            default:
                // @todo: warn? error?
                break;
        }
    }

    protected function getColor($color, $alpha, $image)
    {
        list($r, $g, $b) = CSSColorParser::parse($color);
        $a = (1 - $alpha) * 127.0;
        return imagecolorallocatealpha($image, $r, $g, $b, $a);
    }

    /**
     * @param string $type (a GeoJson type name)
     * @return array
     */
    protected function getDefaultFeatureStyle($type)
    {
        return array(
            'strokeWidth' => 1,
            'fontColor' => '#ff0000',
            'labelOutlineColor' => '#ffffff',
            'strokeDashstyle' => 'solid',
        );
    }

    /**
     * @param mixed[] $geometry
     * @return array
     */
    protected function getFeatureStyle($geometry)
    {
        $defaults = $this->getDefaultFeatureStyle($geometry['type']);
        return array_replace($defaults, $geometry['style']);
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawPolygon($canvas, $geometry)
    {
        // promote to single-item MultiPolygon and delegate
        $multiPolygon = array_replace($geometry, array(
            'type' => 'MultiPolygon',
            'coordinates' => array($geometry['coordinates']),
        ));
        $this->drawMultiPolygon($canvas, $multiPolygon);
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawMultiPolygon($canvas, $geometry)
    {
        $image = $canvas->resource;
        $style = $this->getFeatureStyle($geometry);
        foreach ($geometry['coordinates'] as $polygon) {
            foreach ($polygon as $ring) {
                if (count($ring) < 3) {
                    continue;
                }

                $points = array();
                foreach ($ring as $c) {
                    $points[] = $canvas->featureTransform->transformPair($c);
                }
                if ($style['fillOpacity'] > 0){
                    $color = $this->getColor($style['fillColor'], $style['fillOpacity'], $image);
                    $canvas->drawPolygonBody($points, $color);
                }
                if ($this->applyStrokeStyle($canvas, $style)) {
                    $canvas->drawPolygonOutline($points, IMG_COLOR_STYLED);
                }
            }
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawLineString($canvas, $geometry)
    {
        // promote to single-item MultiLineString and delegate
        $mlString = array_replace($geometry, array(
            'type' => 'MultiLineString',
            'coordinates' => array($geometry['coordinates']),
        ));
        $this->drawMultiLineString($canvas, $mlString);
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawMultiLineString($canvas, $geometry)
    {
        $style = $this->getFeatureStyle($geometry);
        if ($this->applyStrokeStyle($canvas, $style)) {
            foreach ($geometry['coordinates'] as $lineString) {
                $pixelCoords = array();
                foreach ($lineString as $coord) {
                    $pixelCoords[] = $canvas->featureTransform->transformPair($coord);
                }
                $canvas->drawLineString($pixelCoords, IMG_COLOR_STYLED);
            }
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawPoint($canvas, $geometry)
    {
        $style = $this->getFeatureStyle($geometry);
        $image = $canvas->resource;
        $resizeFactor = $canvas->featureTransform->lineScale;

        $p = $canvas->featureTransform->transformPair($geometry['coordinates']);
        $p[0] = round($p[0]);
        $p[1] = round($p[1]);

        if (isset($style['label'])) {
            // draw label with halo
            $color = $this->getColor($style['fontColor'], 1, $image);
            $bgcolor = $this->getColor($style['labelOutlineColor'], 1, $image);
            $font = "{$this->fontPath}/OpenSans-Bold.ttf";

            $fontSize = floatval(10 * $resizeFactor);
            $haloOffsets = array(
                array(0, +$resizeFactor),
                array(0, -$resizeFactor),
                array(-$resizeFactor, 0),
                array(+$resizeFactor, 0),
            );
            // offset text to the right of the point
            $textXy = array(
                $p[0] + $resizeFactor * 1.5 * $style['pointRadius'],
                // center vertically on original y
                $p[1] + 0.5 * $fontSize,
            );
            $text = $style['label'];
            foreach ($haloOffsets as $xy) {
                imagettftext($image, $fontSize, 0,
                    $textXy[0] + $xy[0], $textXy[1] + $xy[1],
                    $bgcolor, $font, $text);
            }
            imagettftext($image, $fontSize, 0,
                $textXy[0], $textXy[1],
                $color, $font, $text);
        }

        $diameter = max(1, round(2 * $style['pointRadius'] * $resizeFactor));
        // Filled circle
        if ($style['fillOpacity'] > 0) {
            $color = $this->getColor(
                $style['fillColor'],
                $style['fillOpacity'],
                $image);
            imagesetthickness($image, 0);
            imagefilledellipse($image, $p[0], $p[1], $diameter, $diameter, $color);
        }
        // Circle border
        if ($this->applyStrokeStyle($canvas, $style)) {
            // Imageellipse DOES NOT support IMG_COLOR_STYLED-based line styling
            // It does not even support line thickness.
            // To support properly styled and patterned point outlining, we have
            // to generate ~circle geometry ourselves and use a styling-aware drawing
            // function.
            $coords = $this->generatePointOutlineCoords($p[0], $p[1], $diameter / 2);
            $canvas->drawPolygonOutline($coords, IMG_COLOR_STYLED);
        }
    }

    /**
     * @param float $centerX in pixel space
     * @param float $centerY in pixel space
     * @param float $radius in pixel space
     * @return float[][]
     */
    protected function generatePointOutlineCoords($centerX, $centerY, $radius)
    {
        $step = min(M_PI / 8, M_PI / 4 / $radius);
        $points = array();
        for ($a = 0; $a < 2 * M_PI; $a += $step) {
            $x = round($centerX + sin($a) * $radius);
            $y = round($centerY + cos($a) * $radius);
            $points[] = array($x, $y);
        }
        return $points;
    }

    /**
     * Generate and apply extended (OpenLayers 2) stroke style.
     * Returns false to indicate that stroke style is degenerate (zero width or zero opacity).
     * Callers should check the return value and skip line rendering completely if false.
     *
     * @param ExportCanvas $canvas
     * @param mixed[] $style
     * @return bool
     */
    protected function applyStrokeStyle($canvas, $style)
    {
        // NOTE: gd imagesetstyle patterns are not based on distance from starting point
        //       on the line, but rather on integral pixel quantities, making it
        //       a) scale proportionally to stroke width
        //       b) fundamentally incompatible with non-integral line widths
        // To generate any functional stroke style, the width must be quantized to an
        // integer, and that integral with must be provided to the style generation function.
        $lineScale = $canvas->featureTransform->lineScale;
        $intThickness = intval(round($style['strokeWidth'] * $lineScale));
        if ($style['strokeOpacity'] && $intThickness >= 1) {
            $color = $this->getColor($style['strokeColor'], $style['strokeOpacity'], $canvas->resource);
            imagesetthickness($canvas->resource, $intThickness);
            $strokeStyle = $this->getStrokeStyle($color, $intThickness, $style['strokeDashstyle'], $lineScale);
            imagesetstyle($canvas->resource, $strokeStyle);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return an array appropriate for gd imagesetstyle that will impact lines
     * drawn with a 'color' value of IMG_COLOR_STYLED.
     * @see http://php.net/manual/en/function.imagesetstyle.php
     *
     * @param int $color from imagecollorallocate
     * @param int $thickness
     * @param string $patternName
     * @param float $patternScale
     * @return array
     */
    protected function getStrokeStyle($color, $thickness, $patternName='solid', $patternScale = 1.0)
    {
        // NOTE: GD actually counts one style entry per produced pixel, NOT per pixel-space length unit.
        // => Length of the style array must scale with the line thickness
        $dotLength = max(1, intval(round($thickness * $patternScale)));
        $dashLength = max(1, intval(round($patternScale * 45)));
        $longDashLength = max(1, intval(round($patternScale * 85)));
        $spaceLength = max(1, intval(round($patternScale * 45)));

        $dot = array_fill(0, $thickness * $dotLength, $color);
        $dash = array_fill(0, $thickness * $dashLength, $color);
        $longdash = array_fill(0, $thickness * $longDashLength, $color);
        $space = array_fill(0, $thickness * $spaceLength, IMG_COLOR_TRANSPARENT);

        switch ($patternName) {
            case 'solid' :
                return array($color);
            case 'dot' :
                return array_merge($dot, $space);
            case 'dash' :
                return array_merge($dash, $space);
            case 'dashdot' :
                return array_merge($dash, $space, $dot, $space);
            case 'longdash' :
                return array_merge($longdash, $space);
            case 'longdashdot' :
                return array_merge($longdash, $space, $dot, $space);
            default:
                throw new \InvalidArgumentException("Unsupported pattern name " . print_r($patternName, true));
        }
    }
}
