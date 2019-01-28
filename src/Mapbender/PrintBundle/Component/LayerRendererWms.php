<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\BufferedSection;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Mapbender\PrintBundle\Component\Export\WmsGrid;
use Mapbender\PrintBundle\Component\Export\WmsGridOptions;
use Mapbender\PrintBundle\Component\Export\WmsTile;
use Mapbender\PrintBundle\Component\Transport\ImageTransport;
use Psr\Log\LoggerInterface;

/**
 * Renders Wms layers in export and print.
 */
class LayerRendererWms extends LayerRenderer
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var ImageTransport */
    protected $imageTransport;
    /** @var int */
    protected $maxGetMapSize;
    /** @var int */
    protected $tileBuffer;

    /**
     * @param ImageTransport $imageTransport
     * @param LoggerInterface $logger
     * @param int $maxGetMapSize
     * @param int $tileBuffer
     */
    public function __construct(ImageTransport $imageTransport, LoggerInterface $logger, $maxGetMapSize, $tileBuffer)
    {
        $this->imageTransport = $imageTransport;
        $this->logger = $logger;
        $this->maxGetMapSize = $maxGetMapSize;
        $this->tileBuffer = $tileBuffer;
        if ($this->maxGetMapSize < 16) {
            throw new \InvalidArgumentException("maxGetMapSize {$this->maxGetMapSize} is too small for stable grid splitting maths");
        }
        if ((3 * $this->tileBuffer) >= $this->maxGetMapSize) {
            throw new \InvalidArgumentException("Tile buffer {$this->tileBuffer} is too large for maxGetMapSize {$this->maxGetMapSize}");
        }
    }

    public function addLayer(ExportCanvas $canvas, $layerDef, Box $extent)
    {
        if (empty($layerDef['url'])) {
            $this->logger->warning("Missing url in WMS layer", $layerDef);
            return;
        }
        $url = $this->preprocessUrl($layerDef, $canvas, $extent);
        $layerImage = $this->getLayerImage($layerDef, $url, $extent);

        if ($layerImage) {
            imagecopyresampled($canvas->resource, $layerImage,
                0, 0, 0, 0,
                $canvas->getWidth(), $canvas->getHeight(),
                imagesx($layerImage), imagesy($layerImage));
            imagedestroy($layerImage);
            unset($layerImage);
        } else {
            $this->logger->warning("Failed request to {$url}");
        }
    }

    /**
     * @param $layerDef
     * @param $nextLayerDef
     * @param Resolution $resolution
     * @return array|bool|false
     */
    public function squashLayerDefinitions($layerDef, $nextLayerDef, $resolution)
    {
        $criteriaA = $this->getSquashCompareCrtiteria($layerDef, $resolution);
        $criteriaB = $this->getSquashCompareCrtiteria($nextLayerDef, $resolution);

        if ($criteriaA === $criteriaB) {
            // layer definitions are compatible
            // concatenate LAYERS= and STYLES=
            // @todo: this should be properly case insensitive
            $queryA = array();
            parse_str(parse_url($layerDef['url'], PHP_URL_QUERY), $queryA);
            $queryB = array();
            parse_str(parse_url($nextLayerDef['url'], PHP_URL_QUERY), $queryB);
            $layersA = ArrayUtil::getDefault($queryA, 'LAYERS', '') ?: ArrayUtil::getDefault($queryA, 'layers', '');
            $layersB = ArrayUtil::getDefault($queryB, 'LAYERS', '') ?: ArrayUtil::getDefault($queryB, 'layers', '');
            $stylesA = ArrayUtil::getDefault($queryA, 'STYLES', '') ?: ArrayUtil::getDefault($queryA, 'styles', '');
            $stylesB = ArrayUtil::getDefault($queryB, 'STYLES', '') ?: ArrayUtil::getDefault($queryB, 'styles', '');
            $newUrl = UrlUtil::validateUrl($layerDef['url'], array(
                'LAYERS' => "{$layersA},{$layersB}",
                'STYLES' => "{$stylesA},{$stylesB}",
            ));
            return array_replace($layerDef, array(
                'url' => $newUrl,
            ));
        } else {
            return false;
        }
    }

    /**
     * @param mixed[] $layerDef
     * @param string $baseUrl
     * @param Box $extent
     * @return resource|null
     */
    protected function getLayerImage($layerDef, $baseUrl, Box $extent)
    {
        $gridOptions = $this->getGridOptions($layerDef);
        // Base grid total dimensions on WITH and HEIGHT in baseUrl. Resolution clamping in extended preprocessUrl may
        // have changed these sizes, so they may no longer match the target canvas size.
        $grid = $this->calculateGridFromUrl($baseUrl, $gridOptions);
        $flipXy = !empty($layerDef['changeAxis']);

        if (count($grid->getTiles()) === 1) {
            // Single-tile grid can trivially be resolved with a single request, avoiding the temporary
            // image used for tile merging.
            $layerImage = $this->imageTransport->downloadImage($baseUrl, $layerDef['opacity']);
        } else {
            $layerImage = imagecreatetruecolor($grid->getWidth(), $grid->getHeight());
            imagesavealpha($layerImage, true);
            imagealphablending($layerImage, false);
            foreach ($grid->getTiles() as $tile) {
                $offsetBox = $tile->getOffsetBox();
                $tileExtent = $tile->getExtent($extent, $grid->getWidth(), $grid->getHeight());
                $params = $this->getBboxAndSizeParams($tileExtent, $offsetBox->getWidth(), $offsetBox->getHeight(), $flipXy);
                $tileUrl = UrlUtil::validateUrl($baseUrl, $params);
                // echo "Next tile request to {$tileUrl}\n";
                $tileImage = $this->imageTransport->downloadImage($tileUrl, $layerDef['opacity']);
                if (!$tileImage) {
                    continue;
                }
                $unbufferedWidth = $tile->getWidth(false);
                $unbufferedHeight = $tile->getHeight(false);
                $buffer = $tile->getBuffer();
                $dstX0 = intval($offsetBox->left + $buffer->left);
                $dstY0 = intval($offsetBox->bottom + $buffer->bottom);
                $srcX0 = intval($buffer->left);
                // mirrored Y params for GD's top-down vs everything else bottom-up Y axis orientation
                $dstY0 = imagesy($layerImage) - ($dstY0 + $unbufferedHeight);
                $srcY0 = intval($buffer->top);

                imagecopyresampled($layerImage, $tileImage,
                          $dstX0, $dstY0,
                          $srcX0, $srcY0,
                          // NOTE: we are never scaling. We have to use *resampled because plain imagecopy
                          //       ignores the alpha channel
                          $unbufferedWidth, $unbufferedHeight,
                          $unbufferedWidth, $unbufferedHeight);
                imagedestroy($tileImage);
            }
        }
        return $layerImage;
    }

    /**
     * @param $layerDef
     * @param GdCanvas $canvas
     * @param Box $extent
     * @return string
     */
    protected function preprocessUrl($layerDef, $canvas, Box $extent)
    {
        $params = $this->getBboxAndSizeParams($extent, $canvas->getWidth(), $canvas->getHeight(), !empty($layerDef['changeAxis']));
        return UrlUtil::validateUrl($layerDef['url'], $params);
    }

    /**
     * @param Box $extent
     * @param int $width
     * @param int $height
     * @param bool $flipXy
     * @return array
     */
    protected function getBboxAndSizeParams(Box $extent, $width, $height, $flipXy)
    {
        $params = array(
            'WIDTH' => intval($width),
            'HEIGHT' => intval($height),
        );
        if ($flipXy) {
            $params['BBOX'] = $extent->bottom . ',' . $extent->left . ',' . $extent->top . ',' . $extent->right;
        } else {
            $params['BBOX'] = $extent->left . ',' . $extent->bottom . ',' . $extent->right . ',' . $extent->top;
        }
        return $params;
    }

    /**
     * Returns the grid options to be used for the layer described by $layerDef.
     * Override this if you need variable, layer-dependent grid options.
     *
     * @param mixed[] $layerDef
     * @return WmsGridOptions
     */
    protected function getGridOptions($layerDef)
    {
        return new WmsGridOptions($this->maxGetMapSize, $this->tileBuffer, $this->tileBuffer);
    }

    /**
     * @param int $width
     * @param int $height
     * @param WmsGridOptions $gridOptions
     * @return WmsGrid
     */
    protected function calculateGrid($width, $height, $gridOptions)
    {
        $rows = $this->calculateLinearBufferedSplit($height,
            $gridOptions->getUnbufferedHeight(), $gridOptions->getBufferVertical());
        $columns = $this->calculateLinearBufferedSplit($width,
            $gridOptions->getUnbufferedWidth(), $gridOptions->getBufferHorizontal());
        $grid = new WmsGrid();
        foreach ($rows as $iy => $row) {
            foreach ($columns as $ix => $column) {
                $grid->addTile(WmsTile::fromSections($column, $row));
            }
        }
        $gridHeight = $grid->getHeight();
        $gridWidth = $grid->getWidth();
        if ($gridHeight != $height) {
            throw new \LogicException("Grid height mismatch {$gridHeight} actual vs expected {$height}");
        }
        if ($gridWidth != $width) {
            throw new \LogicException("Grid width mismatch {$gridWidth} actual vs expected {$width}");
        }
        return $grid;
    }

    /**
     * Calculates a grid based on WIDTH and HEIGHT params extracted from a prepared WMS GetMap request url.
     *
     * @param string $url
     * @param WmsGridOptions $gridOptions
     * @return WmsGrid
     */
    protected function calculateGridFromUrl($url, $gridOptions)
    {
        $urlParams = array();
        parse_str(parse_url($url, PHP_URL_QUERY), $urlParams);
        $layerWidth = intval($urlParams['WIDTH']);
        $layerHeight = intval($urlParams['HEIGHT']);

        return $this->calculateGrid($layerWidth, $layerHeight, $gridOptions);
    }

    /**
     * @param int $total
     * @param int $unbufferedSegmentLength
     * @param int $bufferLength
     * @return BufferedSection[]
     */
    protected function calculateLinearBufferedSplit($total, $unbufferedSegmentLength, $bufferLength = 0)
    {
        // Step 1: calculate non-overlapping segments, allowing the first and the last segments
        //         to be longer than $unbufferedSegmentLength by $bufferLength
        $unbufferedSegments = array();
        for ($offset = 0; $offset < $total; ) {
            $allowedSegmentLength = $unbufferedSegmentLength;
            if ($offset === 0) {
                // add real length to first section (will not need buffer before it)
                $allowedSegmentLength += $bufferLength;
            }
            if ($offset + $allowedSegmentLength + $bufferLength >= $total) {
                // add real length to last section (will not need buffer after it)
                $allowedSegmentLength += $bufferLength;
            }
            $nextLength = min($allowedSegmentLength, $total - $offset);
            $unbufferedSegments[] = $nextLength;
            $offset += $nextLength;
        }
        // if the last segment is very short, redistribute some length from the full-length prior-to-last segment to it
        $minIncrement = min($total, max(intval(0.5 * $bufferLength), 8));
        $lastIndex = count($unbufferedSegments) - 1;
        if ($lastIndex > 0 && $unbufferedSegments[$lastIndex] < $minIncrement) {
            $unbufferedSegments[$lastIndex] += $minIncrement;
            $unbufferedSegments[$lastIndex - 1] -= $minIncrement;
        }
        if (array_sum($unbufferedSegments) !== $total) {
            throw new \LogicException("Split lengths do not add up to expected total {$total}. Actual sum: " . array_sum($unbufferedSegments));
        }
        // extend all unbuffered segments by stretching them, which also makes them overlap
        $bufferedSegments = array();
        $nextOffset = 0;
        foreach ($unbufferedSegments as $i => $currentSegment) {
            $segOffset = $nextOffset;
            $nextOffset += $currentSegment;
            if ($i > 0) {
                $bufferBefore = $bufferLength;
            } else {
                $bufferBefore = 0;
            }
            if ($i < $lastIndex) {
                $bufferAfter = $bufferLength;
            } else {
                $bufferAfter = 0;
            }
            $bufferedSegments[] = new BufferedSection($segOffset, $currentSegment, $bufferBefore, $bufferAfter);
        }
        return $bufferedSegments;
    }

    /**
     * @param mixed[] $layerDef
     * @param Resolution $resolution
     * @return mixed[]
     */
    protected function getSquashCompareCrtiteria($layerDef, $resolution)
    {
        $ignoredParams = array(
            'LAYERS',
            'STYLES',
            '_OLSALT',
            'WIDTH',
            'HEIGHT',
            '_SIGNATURE',
        );

        $data = array(
            'url' => UrlUtil::validateUrl($layerDef['url'], array(), $ignoredParams),
            // Client may submit sourceId to actually prevent squashing of layers that look and feel compatible
            // (= effectively the same source in an application mulitple times)
            'sourceId' => ArrayUtil::getDefault($layerDef, 'sourceId', null),
            // Adjacent layers from the same source that have the same min / max resolution can always be
            // squashed safely.
            'minResolution' => ArrayUtil::getDefault($layerDef, 'minResolution', null),
            'maxResolution' => ArrayUtil::getDefault($layerDef, 'maxResolution', null),
        );

        // Add comparison criteria based on min / max resolution of the layers, but
        // also taking into account the actual required resolution for this job.
        // This allows squashing more. I.e. two layers that have differen min / max resolutions,
        // but where both can be queried fine at the job resolution without adjust width / height
        // can be squashed into a single request
        $minResRequired = min($resolution->getHorizontal(), $resolution->getVertical());
        $maxResRequired = max($resolution->getHorizontal(), $resolution->getVertical());
        if ($data['minResolution'] !== null && $data['minResolution'] <= $minResRequired) {
            $data['minResolution'] = null;
        }
        if ($data['maxResolution'] !== null && $data['maxResolution'] >= $maxResRequired) {
            $data['maxResolution'] = null;
        }
        return $data;
    }
}
