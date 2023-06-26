<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Geometry\LineLoopIterator;
use Mapbender\PrintBundle\Component\Geometry\LineStringIterator;
use Mapbender\PrintBundle\Util\CoordUtil;
use Mapbender\PrintBundle\Util\GdUtil;
use Mapbender\Utils\InfiniteCyclicArrayIterator;

/**
 * Renders "GeoJSON+Style" layers in image export and print.
 * These are technically not valid GeoJSON because they use a
 * non-conformant "style" entry inside features.
 */
class LayerRendererGeoJson extends LayerRenderer
{
    /** @var string */
    protected $fontPath;
    /** @var LayerRendererMarkers */
    protected $markerRenderer;

    /**
     * @param string $fontPath
     * @param LayerRendererMarkers $markerRenderer
     */
    public function __construct($fontPath, LayerRendererMarkers $markerRenderer)
    {
        $this->fontPath = rtrim($fontPath, '/');
        $this->markerRenderer = $markerRenderer;
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
            case 'multipoint' :
                $this->drawMultiPoint($canvas, $feature);
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
        // see http://dev.openlayers.org/releases/OpenLayers-2.13.1/docs/files/OpenLayers/Feature/Vector-js.html#OpenLayers.Feature.Vector.Constants
        return array(
            'strokeWidth' => 1,
            'strokeOpacity' => 1,
            'fontColor' => '#ff0000',
            'labelOutlineColor' => '#ffffff',
            'strokeDashstyle' => 'solid',
            'labelAlign' => 'cm',
            'labelXOffset' => 0,
            'labelYOffset' => 0,
            'fontOpacity' => 1,
            'pointRadius' => 6,
            'fillOpacity' => 0.4,
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
        $mergedStyle = array_replace($defaults, $filteredStyle) + $geometry['style'];
        // forward fontOpacity => labelOutlineOpacity default
        // see http://dev.openlayers.org/releases/OpenLayers-2.13.1/docs/files/OpenLayers/Feature/Vector-js.html#OpenLayers.Feature.Vector.Constants
        if (!isset($mergedStyle['labelOutlineOpacity'])) {
            $mergedStyle['labelOutlineOpacity'] = $mergedStyle['fontOpacity'];
        }
        return $mergedStyle;
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
        $ringCenters = array();

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
                // Only include the first ring (=outer boundary) in centroid. Ignore interior rings (~=donut cutouts)
                if (!$ringIx) {
                    $ringCenters[] = CoordUtil::getRingCentroid($transformedRing);
                }
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
        if (!$bounds->isEmpty() && $this->checkLineStyleVisibility($canvas, $style)) {
            $lineCoordSets = call_user_func_array('\array_merge', $transformedRings);
            $this->drawLineSetsInternal($canvas, $style, $lineCoordSets, true, $bounds);
        }
        if (!$bounds->isEmpty() && !empty($style['label'])) {
            $anchor = CoordUtil::getAverage($ringCenters);
            $this->drawFeatureLabel($canvas, $style, $style['label'], $anchor);
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
        $nCentroidPoints = 0;
        $centroidSums = array(
            'x' => 0.0,
            'y' => 0.0,
        );
        $style = $this->getFeatureStyle($geometry);
        $transformedCoordSets = array();
        foreach ($geometry['coordinates'] as $lineString) {
            $transformedLineCoords = array();
            foreach ($lineString as $coord) {
                $transformedLineCoords[] = $canvas->featureTransform->transformPair($coord);
            }
            // OL6 line label is anchored on center vertex (odd coordinate count) or
            // halfway between two center vertices (even coordinate count)
            // Multilines apply that logic to each line individually, and then use
            // over every individual line label position
            /** @see https://github.com/openlayers/openlayers/blob/v6.4.3/src/ol/render/canvas/TextBuilder.js#L244 */
            /** @see https://github.com/openlayers/openlayers/blob/v6.4.3/src/ol/render/canvas/TextBuilder.js#L250 */
            $midPointOffset = max(0, floor((count($transformedLineCoords) - 1) / 2));
            if (count($transformedLineCoords) % 2) {
                // Odd vertex count; use center
                $centroidSums['x'] += $transformedLineCoords[$midPointOffset][0];
                $centroidSums['y'] += $transformedLineCoords[$midPointOffset][1];
                $nCentroidPoints += 1;
            } else {
                // Even vertex count; average the two middle ones
                $centroidSums['x'] += $transformedLineCoords[$midPointOffset][0] + $transformedLineCoords[$midPointOffset + 1][0];
                $centroidSums['y'] += $transformedLineCoords[$midPointOffset][1] + $transformedLineCoords[$midPointOffset + 1][1];
                $nCentroidPoints += 2;
            }

            $transformedCoordSets[] = $transformedLineCoords;
        }
        if ($this->checkLineStyleVisibility($canvas, $style)) {
            $this->drawLineSetsInternal($canvas, $style, $transformedCoordSets, false);
        }
        if ($nCentroidPoints && !empty($style['label'])) {
            $centroid = array(
                $centroidSums['x'] / $nCentroidPoints,
                $centroidSums['y'] / $nCentroidPoints,
            );
            $this->drawFeatureLabel($canvas, $style, $style['label'], $centroid);
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawPoint($canvas, $geometry)
    {
        // promote to single-item MultiPoint and delegate
        $mPoint = array_replace($geometry, array(
            'type' => 'MultiPoint',
            'coordinates' => array($geometry['coordinates']),
        ));
        $this->drawMultiPoint($canvas, $mPoint);
    }

    /**
     * @param ExportCanvas $canvas
     * @param mixed[] $geometry
     */
    protected function drawMultiPoint($canvas, $geometry)
    {

        $style = $this->getFeatureStyle($geometry);
        $resizeFactor = $canvas->featureTransform->lineScale;

        foreach ($geometry['coordinates'] as $point) {

            $p = $canvas->featureTransform->transformPair($point);
            $p[0] = round($p[0]);
            $p[1] = round($p[1]);

            $diameter = max(1, round(2 * $style['pointRadius'] * $resizeFactor));
            if (isset($style['fillColor']) && $style['fillOpacity'] > 0) {
                $color = $this->getColor(
                    $style['fillColor'],
                    $style['fillOpacity'],
                    $canvas->resource);
                $canvas->drawFilledCircle($p[0], $p[1], $color, $diameter);
            }


            if (isset($style['strokeColor']) && $style['strokeOpacity'] > 0 && $style['strokeWidth']) {
                $strokeWidth = max(0, intval(round($style['strokeWidth'] * $canvas->featureTransform->lineScale)));
                if ($strokeWidth > 0) {
                    $strokeColor = $this->getColor($style['strokeColor'], $style['strokeOpacity'], $canvas->resource);
                    $this->drawCircleOutline($canvas, $p[0], $p[1], $diameter / 2, $strokeColor, $strokeWidth);
                    imagecolordeallocate($canvas->resource, $strokeColor);
                }
            }
            if (!empty($style['label'])) {
                $this->drawFeatureLabel($canvas, $style, $style['label'], $p);
            }
            if (!empty($style['externalGraphic'])) {
                $anchorXy = array(
                    'x' => $p[0],
                    'y' => $p[1],
                );
                $this->markerRenderer->addFeatureGraphic($canvas, $anchorXy, $style);
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
        $transparent = $tempCanvas->getTransparent();

        $innerDiameter = intval(round(2 * ($radius - 0.5 * $width)));
        $outerDiameter = intval(round(2 * ($radius + 0.5 * $width)));
        $tempCanvas->drawFilledCircle($centerX, $centerY, $color, $outerDiameter);
        if ($innerDiameter > 0) {
            // stamp out a fully transparent circle
            $tempCanvas->drawFilledCircle($centerX, $centerY, $transparent, $innerDiameter);
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
     * @param float[] $centroid in pixel space
     */
    protected function drawFeatureLabel(ExportCanvas $canvas, $style, $text, $centroid)
    {
        $color = $this->getColor($style['fontColor'], $style['fontOpacity'], $canvas->resource);
        $bgcolor = $this->getColor($style['labelOutlineColor'], $style['labelOutlineOpacity'], $canvas->resource);
        $fontName = $this->getLabelFont($style);
        $fontSize = $this->getLabelFontSize($canvas, $style);
        $textSize = GdUtil::getTtfTextSize($fontName, $fontSize, $text);
        $anchor = $this->getFeatureLabelAnchor($canvas, $style, $centroid, $textSize[0], $textSize[1]);

        // @todo: evaluate 'labelOutlineWidth' from style
        // @todo: skip halo rendering if label style indicates no outline width, or fully transparent outline
        $haloSubCanvas = $canvas->getSubRegion(
            intval($anchor[0] - 8), intval($anchor[1] - $textSize[1] - 8),
            intval($textSize[0] + 16), intval($textSize[1] + 16));

        $haloFactor = $canvas->featureTransform->lineScale;
        $haloOffsets = array(
            array(0, +$haloFactor),
            array(0, -$haloFactor),
            array(-$haloFactor, 0),
            array(+$haloFactor, 0),
        );
        foreach ($haloOffsets as $xy) {
            imagettftext($haloSubCanvas->resource, $fontSize, 0,
                $anchor[0] + $xy[0] - $haloSubCanvas->getOffsetX(),
                $anchor[1] + $xy[1] - $haloSubCanvas->getOffsetY(),
                $bgcolor, $fontName, $text);
        }
        $haloSubCanvas->mergeBack();
        imagettftext($canvas->resource, $fontSize, 0,
            $anchor[0], $anchor[1],
            $color, $fontName, $text);
    }

    /**
     * Calculates the appropriate starting position (for GD imagettftext) for a feature label, given the feature's
     * centroid and style.
     *
     * @param ExportCanvas $canvas
     * @param array $style
     * @param float[] $centroid x/y in pixel space, expected to be numerically indexed
     * @param float $textWidth in pixels
     * @param float $textHeight in pixels
     * @return float[]
     */
    protected function getFeatureLabelAnchor(ExportCanvas $canvas, $style, $centroid, $textWidth, $textHeight)
    {
        $offsetScale = $canvas->featureTransform->lineScale;

        // Push label off centroid according to 'labelAlign', 'labelXOffset' and 'labelYOffset'. Default is 'cm', 0, 0.
        // see http://dev.openlayers.org/releases/OpenLayers-2.13.1/docs/files/OpenLayers/Feature/Vector-js.html#OpenLayers.Feature.Vector.Constants
        switch (substr($style['labelAlign'], 0, 1)) {
            case 'r':
                $x = $centroid[0] - $textWidth + $offsetScale * $style['labelXOffset'];
                break;
            default:
            case 'c':
                $x = $centroid[0] - 0.5 * $textWidth + $offsetScale * $style['labelXOffset'];
                break;
            case 'l':
                $x = $centroid[0] + $offsetScale * $style['labelXOffset'];
                break;
        }
        switch (substr($style['labelAlign'], 1, 1)) {
            case 'b':
                $y = $centroid[1]  + $offsetScale * $style['labelYOffset'];
                break;
            default:
            case 'm':
                $y = $centroid[1] + 0.5 * $textHeight + $offsetScale * $style['labelYOffset'];
                break;
            case 't':
                $y = $centroid[1] + 1 * $textHeight + $offsetScale * $style['labelYOffset'];
                break;
        }
        return array($x, $y);
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
     * Draws a multitude of line loops (=polygon outlines) or line strings. Coordinate sets passed in are assumed
     * to be already transformed to the given $canvas's pixel space.
     *
     * @param ExportCanvas $canvas
     * @param array $style
     * @param float[][][] $coordSets in pixel space
     * @param boolean $close; use true for polygons, false for line strings
     * @param FeatureBounds|null $bounds of all $rings; will be calculated if omitted, but can be passed in as an optimization
     */
    protected function drawLineSetsInternal(ExportCanvas $canvas, $style, $coordSets, $close, FeatureBounds $bounds=null)
    {
        if (!$bounds) {
            $bounds = new FeatureBounds();
            foreach ($coordSets as $lineCoords) {
                $bounds->addPoints($lineCoords);
            }
        }
        if ($bounds->isEmpty() || !$coordSets) {
            // nothing to render, avoiding errors down the line
            return;
        }

        $lineScale = $canvas->featureTransform->lineScale;
        $bufferWidth = intval($style['strokeWidth'] *  $lineScale + 5);
        $subRegion = $this->getSubRegionFromBounds($canvas, $bounds, $bufferWidth);

        $pixelThickness = $style['strokeWidth'] * $lineScale;
        $intThickness = max(1, intval(round($pixelThickness)));
        $opacity = $style['strokeOpacity'];
        if ($pixelThickness < 1) {
            $opacity = max(0.0, $opacity * $pixelThickness);
        }
        $patternName = $style['strokeDashstyle'];
        $lineColor = $this->getColor($style['strokeColor'], $opacity, $subRegion->resource);
        if ($intThickness > 5 || $patternName !== 'solid') {
            // Native gd rendering doesn't do patterns well, and starts looking bad for solid lines above
            // certain thickness.
            // Generate, under great pain, a bunch of rendering primitives that form the line pattern when
            // combined.
            foreach ($coordSets as $lineCoords) {
                $patternFragments = $this->generatePatternFragments($patternName, $lineCoords, $lineScale, $close);
                $this->renderPatternFragments($subRegion, $patternFragments, $lineColor, $intThickness);
            }
        } else {
            imagesetthickness($subRegion->resource, $intThickness);
            foreach ($coordSets as $lineCoords) {
                if ($close) {
                    $subRegion->drawPolygonOutline($lineCoords, $lineColor);
                } else {
                    $subRegion->drawLineString($lineCoords, $lineColor);
                }
            }
        }

        $subRegion->mergeBack();
    }

    /**
     * @param ExportCanvas $canvas
     * @param array $style
     * @return bool
     */
    protected function checkLineStyleVisibility($canvas, $style)
    {
        $thickness = $style['strokeWidth'] * $canvas->featureTransform->lineScale;
        if ($thickness <= 0) {
            return false;
        } elseif ($thickness <= 1) {
            // for very thin lines, multiply opacity with thickness
            return $thickness * $style['strokeOpacity'] >= ExportCanvas::MINIMUM_OPACITY;
        } else {
            // Check if line opacity is flat-out zero, or too small to produce a visible result
            return $style['strokeOpacity'] >= ExportCanvas::MINIMUM_OPACITY;
        }
    }

    /**
     * @param ExportCanvas $canvas
     * @param array $style
     * @return float
     */
    protected function getLabelFontSize(ExportCanvas $canvas, $style)
    {
        $fontSizeRule = !empty($style['fontSize']) ? $style['fontSize'] : '10px';
        $fontSize = \floatval(\preg_replace('#[^\d]*$#', '', $fontSizeRule)) ?: 10;
        return floatval($fontSize * $canvas->featureTransform->lineScale);
    }

    /**
     * Should return an absolute path to the appropriate .ttf file for rendering a feature label.
     *
     * @param array $style
     * @return string
     */
    protected function getLabelFont($style)
    {
        // @todo: check existance of ttf, fall back to default if no such file
        $fontWeightRule = !empty($style['fontWeight']) ? $style['fontWeight'] : 'regular';
        // @todo: undo capitalization of file name!
        $suffix = \ucfirst($fontWeightRule);
        if (\file_exists("{$this->fontPath}/OpenSans-{$suffix}.ttf")) {
            return "{$this->fontPath}/OpenSans-{$suffix}.ttf";
        } else {
            // Hope for the best
            return "{$this->fontPath}/OpenSans-Regular.ttf";
        }
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

    /**
     * Build list of draw segments and gap segments from either
     * 1) Canvas / Openlayers 3/4/5/6 stroke line dash (array of integers)
     * or
     * 2) Legacy SVG / Openlayers 2 pattern name (string)
     * @param string|integer[] $pattern
     * @return array[]
     */
    protected function normalizeLinePattern($pattern)
    {
        $infiniteDraw = array(
            'type' => 'draw',
            'length' => null,
        );
        if (!$pattern || $pattern === 'solid') {
            /** No gaps anywhere */
            return array($infiniteDraw);
        }
        if (\is_array($pattern)) {
            // Repeat pattern if odd number of components
            /** @see https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/setLineDash#parameters */
            if (count($pattern) % 2) {
                $pattern = \array_values($pattern);
                $pattern = \array_merge($pattern, $pattern);
            }

            $descriptors = array();
            $drawToggle = true;
            foreach ($pattern as $length) {
                $descriptors[] = array(
                    'length' => $length,
                    'type' => $drawToggle ? 'draw' : 'gap',
                );
                $drawToggle = !$drawToggle;
            }
            return $descriptors;
        }

        $dot = array(
            'type' => 'dot',
            'length' => 0,
        );
        $gap = array(
            'type' => 'gap',
            'length' => 45,
        );
        $dash = array(
            'type' => 'draw',
            'length' => 45,
        );
        $longDash = array(
            'type' => 'draw',
            'length' => 85,
        );
        switch ($pattern) {
            case 'solid' :
                return array(
                    $infiniteDraw,
                );
            case 'dot' :
                return array(
                    $dot,
                    $gap,
                );
            case 'dash' :
                return array(
                    $dash,
                    $gap,
                );
            case 'dashdot' :
                return array(
                    $dash,
                    $gap,
                    $dot,
                    $gap,
                );
            case 'longdash' :
                return array(
                    $longDash,
                    $gap,
                );
            case 'longdashdot':
                return array(
                    $longDash,
                    $gap,
                    $dot,
                    $gap,
                );
            default:
                throw new \InvalidArgumentException("Unsupported pattern name " . print_r($pattern, true));
        }
    }

    /**
     * @param string|integer[] $pattern
     * @param $lineCoords
     * @param float $patternScale
     * @param boolean $closeLoop
     * @return array[]
     */
    protected function generatePatternFragments($pattern, $lineCoords, $patternScale, $closeLoop)
    {
        $dots = array();
        $lines = array();
        $lineCoords = array_values($lineCoords);
        if ($closeLoop) {
            $segmentIterator = new LineLoopIterator($lineCoords);
        } else {
            $segmentIterator = new LineStringIterator($lineCoords);
        }

        $descriptors = $this->normalizeLinePattern($pattern);
        $descriptorIterator = new InfiniteCyclicArrayIterator($descriptors);
        $currentDescriptor = $descriptorIterator->current();
        $descriptorLengthLeft = $currentDescriptor['length'];

        foreach ($segmentIterator as $lineSegment) {
            $segmentLength = $lineSegment->getLength();
            $segmentLengthLeft = $segmentLength;
            $nextFragmentStart = 0;
            while ($segmentLengthLeft > 0) {
                if ($descriptorLengthLeft !== null) {
                    $takenSegmentLength = min($segmentLengthLeft, $descriptorLengthLeft * $patternScale);
                    $takenDescriptorLength = $takenSegmentLength / $patternScale;
                } else {
                    $takenSegmentLength = $segmentLengthLeft;
                    $takenDescriptorLength = null;
                }
                switch ($currentDescriptor['type']) {
                    case 'dot':
                        $dots[] = $lineSegment->getPointAtLenghtOffset($nextFragmentStart)->toArray();
                        break;
                    case 'draw':
                        // Produce an end-cappend line segment
                        $drawSegment = $lineSegment->getSlice($nextFragmentStart, $takenSegmentLength);
                        $lines[] = $drawSegment->toArray();
                        // NOTE: gd lines with any thickness > 1 will have their edges rendered 'perfectly' vertically
                        //       or horizontally, with no angles.
                        //       We end-cap the lines with circles to give them a more pleasant appearance.
                        //       This also has the very nice side benefit of putting a circle on every vertex joint,
                        //       rounding out those edges, too.
                        // @todo: suppress the intermittent point at very obtuse vertex angles to reduce noise
                        $dots[] = $drawSegment->getStart()->toArray();
                        $dots[] = $drawSegment->getEnd()->toArray();
                        break;
                    default:
                    case 'gap':
                        // nothing to do
                        break;
                }
                if ($takenDescriptorLength !== null && $descriptorLengthLeft !== null) {
                    $descriptorLengthLeft -= $takenDescriptorLength;
                    if ($descriptorLengthLeft <= 0) {
                        $descriptorIterator->next();
                        $currentDescriptor = $descriptorIterator->current();
                        $nextDescriptorLength = $currentDescriptor['length'];
                        if ($nextDescriptorLength === null) {
                            $descriptorLengthLeft = null;
                        } else {
                            $descriptorLengthLeft += $nextDescriptorLength;
                        }
                    }
                }
                $segmentLengthLeft -= $takenSegmentLength;
                $nextFragmentStart += $takenSegmentLength;
            }
        }
        return array(
            'dots' => $dots,
            'lines' => $lines,
        );
    }

    /**
     * Render individual dot and line primitives.
     * @see generatePatternFragments
     *
     * @param GdCanvas $canvas
     * @param array $fragments
     * @param int $color GDish
     * @param int $thickness used for both line thickness and dot diameter
     */
    protected function renderPatternFragments(GdCanvas $canvas, $fragments, $color, $thickness)
    {
        imagesetthickness($canvas->resource, $thickness);
        foreach ($fragments['dots'] as $dot) {
            $canvas->drawFilledCircle($dot[0], $dot[1], $color, $thickness);
        }
        foreach ($fragments['lines'] as $line) {
            $canvas->drawLineString($line, $color);
        }
    }
}
