<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Utils\ArrayUtil;
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

    /**
     * @param $layerDef
     * @param $nextLayerDef
     * @param Export\Resolution $resolution
     * @return array|bool|false
     */
    public function squashLayerDefinitions($layerDef, $nextLayerDef, $resolution)
    {
        // Fold everything, maintaining feature order
        $featuresKeys = array(
            'geometries',   // legacy
            'features',     // conformant GeoJson
        );
        $nextLayerFeatures = false;
        foreach ($featuresKeys as $featuresKey) {
            if (array_key_exists($featuresKey, $nextLayerDef)) {
                $nextLayerFeatures = $nextLayerDef[$featuresKey];
                break;
            }
        }
        foreach ($featuresKeys as $featuresKey) {
            if ($nextLayerFeatures && array_key_exists($featuresKey, $layerDef)) {
                $layerDef[$featuresKey] = array_merge($layerDef[$featuresKey], $nextLayerFeatures);
                break;
            }
        }
        return $layerDef;
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
        // Special snowflake Digitizer can and will supply NULL for required
        // style attributes, nuking our defaults. Filter those NULLs, if we have
        // a default value for them.
        $filteredStyle = array_filter($geometry['style'], function($value) {
            return $value !== null;
        });
        return array_replace($defaults, $filteredStyle) + $geometry['style'];
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
        // remove label text, if any, from ephemereal MultiPolygon for separate processing
        $labelText = ArrayUtil::getDefault($geometry['style'], 'label', null);
        unset($geometry['style']['label']);
        $this->drawMultiPolygon($canvas, $multiPolygon);
        // @todo: render feature label using $labelText
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawMultiPolygon($canvas, $geometry)
    {
        $style = $this->getFeatureStyle($geometry);
        $transformedRings = array();
        $bounds = new FeatureBounds();
        foreach ($geometry['coordinates'] as $polygonIndex => $polygon) {
            $transformedRings[$polygonIndex] = array();
            foreach ($polygon as $ringIx => $ring) {
                if (count($ring) < 3) {
                    continue;
                }

                $transformedRing = array();
                foreach ($ring as $c) {
                    $transformedRing[] = $canvas->featureTransform->transformPair($c);
                }
                $bounds->addPoints($transformedRing);
                $transformedRings[$polygonIndex][$ringIx] = $transformedRing;
            }
        }
        if (!$bounds->isEmpty() && $style['fillOpacity'] > 0) {
            $subRegion = $this->getSubRegionFromBounds($canvas, $bounds, 10);
            $styleColor = $this->getColor($style['fillColor'], $style['fillOpacity'], $subRegion->resource);
            foreach ($transformedRings as $polygonRings) {
                $currentRingColor = $styleColor;
                foreach ($polygonRings as $ringPoints) {
                    $subRegion->drawPolygonBody($ringPoints, $currentRingColor);
                    // Set color to transparent for next ring
                    // Rings after the first are interior rings, which we effectively "undraw"
                    // by using transparent
                    $currentRingColor = $subRegion->getTransparent();
                }
            }
            $subRegion->mergeBack();
        }
        if (!$bounds->isEmpty() && $this->applyStrokeStyle($canvas, $style, $canvas->featureTransform->lineScale)) {
            $bufferWidth = intval($style['strokeWidth'] * $canvas->featureTransform->lineScale + 5);
            $subRegion = $this->getSubRegionFromBounds($canvas, $bounds, $bufferWidth);
            $this->applyStrokeStyle($subRegion, $style, $canvas->featureTransform->lineScale);
            foreach ($transformedRings as $polygonRings) {
                foreach ($polygonRings as $ringPoints) {
                    $subRegion->drawPolygonOutline($ringPoints, IMG_COLOR_STYLED);
                }
            }
            $subRegion->mergeBack();
        }
        // @todo: detect and render feature label
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
        // remove label text, if any, from ephemereal MultiLineString for separate processing
        $labelText = ArrayUtil::getDefault($geometry['style'], 'label', null);
        unset($geometry['style']['label']);
        $this->drawMultiLineString($canvas, $mlString);
        // @todo: render label using extracted $labelText
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawMultiLineString($canvas, $geometry)
    {
        $style = $this->getFeatureStyle($geometry);
        if ($this->applyStrokeStyle($canvas, $style, $canvas->featureTransform->lineScale)) {
            foreach ($geometry['coordinates'] as $lineString) {
                $pixelCoords = array();
                foreach ($lineString as $coord) {
                    $pixelCoords[] = $canvas->featureTransform->transformPair($coord);
                }
                $canvas->drawLineString($pixelCoords, IMG_COLOR_STYLED);
            }
        }
        // @todo: detect and render feature label
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawPoint($canvas, $geometry)
    {
        $style = $this->getFeatureStyle($geometry);
        $resizeFactor = $canvas->featureTransform->lineScale;

        $p = $canvas->featureTransform->transformPair($geometry['coordinates']);
        $p[0] = round($p[0]);
        $p[1] = round($p[1]);

        if (isset($style['label'])) {
            // offset text to the right of the point
            $textXy = array(
                $p[0] + $resizeFactor * 1.5 * $style['pointRadius'],
                // center vertically on original y
                $p[1] + 0.5 * $this->getLabelFontSize($canvas, $style),
            );
            $this->drawFeatureLabel($canvas, $style, $style['label'], $textXy);
        }

        $diameter = max(1, round(2 * $style['pointRadius'] * $resizeFactor));
        // Filled circle
        if ($style['fillOpacity'] > 0) {
            $color = $this->getColor(
                $style['fillColor'],
                $style['fillOpacity'],
                $canvas->resource);
            imagesetthickness($canvas->resource, 0);
            imagefilledellipse($canvas->resource, $p[0], $p[1], $diameter, $diameter, $color);
        }
        if ($style['strokeOpacity'] > 0 && $style['strokeWidth']) {
            $strokeWidth = max(0, intval(round($style['strokeWidth'] * $canvas->featureTransform->lineScale)));
            if ($strokeWidth > 0) {
                $strokeColor = $this->getColor($style['strokeColor'], $style['strokeOpacity'], $canvas->resource);
                $this->drawCircleOutline($canvas, $p[0], $p[1], $diameter / 2, $strokeColor, $strokeWidth);
                imagecolordeallocate($canvas->resource, $strokeColor);
            }
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param int $centerX
     * @param int $centerY
     * @param int $radius
     * @param int $color GDish
     * @param int $width
     */
    protected function drawCircleOutline($canvas, $centerX, $centerY, $radius, $color, $width)
    {
        // imageellipse does not support thickness or styling
        // => draw the outline on a temp image by first drawing an outer filled circle,
        //    then cut out the inside of the ring by rendering another, smaller circle
        //    on top of it with a fully transparent color with blending disabled
        $offsetXy = intval($radius + $width + 1);
        $sizeWh = 2 * $offsetXy;
        $tempCanvas = $canvas->getSubRegion($centerX - $offsetXy, $centerY - $offsetXy, $sizeWh, $sizeWh);
        $tempImage = $tempCanvas->resource;
        $transparent = $tempCanvas->getTransparent();

        $innerDiameter = intval(round(2 * ($radius - 0.5 * $width)));
        $outerDiameter = intval(round(2 * ($radius + 0.5 * $width)));
        imagefilledellipse($tempImage, $offsetXy, $offsetXy, $outerDiameter, $outerDiameter, $color);
        if ($innerDiameter > 0) {
            // stamp out a fully transparent circle
            imagefilledellipse($tempImage, $offsetXy, $offsetXy, $innerDiameter, $innerDiameter, $transparent);
        }
        $tempCanvas->mergeBack();
    }

    /**
     * Should render the label for a feature. This is not a freestanding ~'label'-type Feature, but a label
     * attached to a geometry, which is getting rendered separately.
     *
     * @param ExportCanvas $canvas
     * @param array $style
     * @param string $text
     * @param float[] $originXy in pixel space
     */
    protected function drawFeatureLabel(ExportCanvas $canvas, $style, $text, $originXy)
    {
        // @todo: evaluate text opacity
        $color = $this->getColor($style['fontColor'], 1, $canvas->resource);
        // @todo: evaluate style's outline opacity (key?) and 'labelOutlineWidth' from style
        $bgcolor = $this->getColor($style['labelOutlineColor'], 1, $canvas->resource);
        $fontName = $this->getLabelFont($style);
        $fontSize = $this->getLabelFontSize($canvas, $style);

        // @todo: skip halo rendering if label style indicates no outline width, or fully transparent outline
        $haloFactor = $canvas->featureTransform->lineScale;
        $haloOffsets = array(
            array(0, +$haloFactor),
            array(0, -$haloFactor),
            array(-$haloFactor, 0),
            array(+$haloFactor, 0),
        );
        foreach ($haloOffsets as $xy) {
            imagettftext($canvas->resource, $fontSize, 0,
                $originXy[0] + $xy[0], $originXy[1] + $xy[1],
                $bgcolor, $fontName, $text);
        }
        imagettftext($canvas->resource, $fontSize, 0,
            $originXy[0], $originXy[1],
            $color, $fontName, $text);
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
     * @param GdCanvas $canvas
     * @param mixed[] $style
     * @param float $lineScale
     * @return bool
     */
    protected function applyStrokeStyle($canvas, $style, $lineScale)
    {
        // NOTE: gd imagesetstyle patterns are not based on distance from starting point
        //       on the line, but rather on integral pixel quantities, making it
        //       a) scale proportionally to stroke width
        //       b) fundamentally incompatible with non-integral line widths
        // To generate any functional stroke style, the width must be quantized to an
        // integer, and that integral with must be provided to the style generation function.
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
            case 'longdashdot':
                return array_merge($longdash, $space, $dot, $space);
            default:
                throw new \InvalidArgumentException("Unsupported pattern name " . print_r($patternName, true));
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param array $style
     * @return float
     */
    protected function getLabelFontSize(ExportCanvas $canvas, $style)
    {
        // @todo: extract font size from style? (not alyays popuplated; empty for 'Redlining' labeled points)
        return floatval(10 * $canvas->featureTransform->lineScale);
    }

    /**
     * Should return an absolute path to the appropriate .ttf file for rendering a feature label.
     *
     * @param array $style
     * @return string
     */
    protected function getLabelFont($style)
    {
        // @todo: extract explicit font setting from style, check existance of ttf, fall back to default if no such file
        return "{$this->fontPath}/OpenSans-Bold.ttf";
    }

    /**
     * @param GdCanvas $canvas
     * @param FeatureBounds $bounds
     * @param int $buffer extra pixels to add around all four edges
     * @return GdSubCanvas
     */
    protected function getSubRegionFromBounds(GdCanvas $canvas, FeatureBounds $bounds, $buffer)
    {
        $offsetX = intval($bounds->getMinX() - $buffer);
        $offsetY = intval($bounds->getMinY() - $buffer);
        $width = intval($bounds->getWidth() + 2 * $buffer + 1);
        $height = intval($bounds->getHeight() + 2 * $buffer + 1);
        // Avoid creating a subregion bigger than the original canvas (e.g. huge polygon stretching way out of visible region)
        // 1: clip origin corner to 0/0
        if ($offsetX < 0) {
            $width -= abs($offsetX);
            $offsetX = 0;
        }
        if ($offsetY < 0) {
            $height -= abs($offsetY);
            $offsetY = 0;
        }
        // 2: clip outer corner to canvas size, but maintain minimum pixel size of 1x1 to avoid errors
        $width = intval(max(1, min($width, $canvas->getWidth() - $offsetX)));
        $height = intval(max(1, min($height, $canvas->getHeight() - $offsetY)));

        return $canvas->getSubRegion($offsetX, $offsetY, $width, $height);
    }
}
