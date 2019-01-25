<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\WmsGrid;
use Mapbender\PrintBundle\Component\Export\WmsTile;
use Mapbender\PrintBundle\Component\Export\WmsTileBuffer;
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

    // tiling parameters; @todo: this should be configurable
    protected $maxGetMapSize = 3072;
    protected $tileBuffer = 512;

    /**
     * @param ImageTransport $imageTransport
     * @param LoggerInterface $logger
     */
    public function __construct(ImageTransport $imageTransport, LoggerInterface $logger)
    {
        $this->imageTransport = $imageTransport;
        $this->logger = $logger;
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
        // die(var_export(array('u0' => $layerDef['url'], 'u1' => $url), true) . "\n");
        $flipXy = !empty($layerDef['changeAxis']);
        $layerImage = $this->getLayerImage($url, $extent, $layerDef['opacity'], $flipXy);

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

    public function squashLayerDefinitions($layerDef, $nextLayerDef)
    {
        // @todo: merge requests with same path and BBOX by appending
        //        LAYERS params left-to-right
        return false;
    }

    /**
     * @param string $url
     * @param Box $extent
     * @param float $opacity
     * @param bool $flipXy
     * @return resource|null
     */
    protected function getLayerImage($url, Box $extent, $opacity, $flipXy)
    {
        // Reextract WIDTH and HEIGHT from url. Resolution clamping in extended preprocessUrl may have changed
        // total request dimensions.
        $urlParams = array();
        parse_str(parse_url($url, PHP_URL_QUERY), $urlParams);
        $layerWidth = intval($urlParams['WIDTH']);
        $layerHeight = intval($urlParams['HEIGHT']);
        $maxUnbufferedTileSize = $this->maxGetMapSize - 2 * $this->tileBuffer;

        $grid = $this->calculateGrid($layerWidth, $layerHeight,
                                     $maxUnbufferedTileSize, $this->tileBuffer);
        // HACK: Force tiling on
        // Non-tiling mode might be a little faster / use less memory, but tiling should always produce the same image.
        // If it doesn't, it needs fixing.
        if (false && count($grid->getTiles()) === 1) {
            $layerImage = $this->imageTransport->downloadImage($url, $opacity);
        } else {
            $layerImage = imagecreatetruecolor($grid->getWidth(), $grid->getHeight());
            imagesavealpha($layerImage, true);
            imagealphablending($layerImage, false);
            foreach ($grid->getTiles() as $tile) {
                $offsetBox = $tile->getOffsetBox();
                $tileExtent = $tile->getExtent($extent, $grid->getWidth(), $grid->getHeight());
                $params = $this->getBboxAndSizeParams($tileExtent, $offsetBox->getWidth(), $offsetBox->getHeight(), $flipXy);
                $tileUrl = UrlUtil::validateUrl($url, $params);
                // echo "Next tile request to {$tileUrl}\n";
                $tileImage = $this->imageTransport->downloadImage($tileUrl, $opacity);
                if (!$tileImage) {
                    continue;
                }
                $unbufferedWidth = $tile->getWidth(false);
                $unbufferedHeight = $tile->getHeight(false);
                $buffer = $tile->getBuffer();
                $dstX0 = intval($offsetBox->left + $buffer->left);
                $dstY0 = imagesy($layerImage) - intval($unbufferedHeight + $offsetBox->bottom + $buffer->bottom);
                $srcX0 = intval($buffer->left);
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
     * @param int $width
     * @param int $height
     * @param int $tileSize
     * @param int $tileBuffer
     * @return WmsGrid
     */
    protected function calculateGrid($width, $height, $tileSize, $tileBuffer = 0)
    {
        $rows = $this->calculateLinearBufferedSplit($height, $tileSize, $tileBuffer);
        $columns = $this->calculateLinearBufferedSplit($width, $tileSize, $tileBuffer);
        $grid = new WmsGrid();
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $offsetX1 = $column['offset'] + $column['length'];
                $offsetY1 = $row['offset'] + $row['length'];
                $offsetBox = new Box($column['offset'], $row['offset'], $offsetX1, $offsetY1);
                $buffer = new WmsTileBuffer($column['discard'][0], $row['discard'][0],
                                            $column['discard'][1], $row['discard'][1]);
                $grid->addTile(new WmsTile($offsetBox, $buffer));
            }
        }
        return $grid;
    }

    /**
     * @param int $total
     * @param int $unbufferedSegmentLength
     * @param int $bufferLength
     * @return int[][]
     */
    protected function calculateLinearBufferedSplit($total, $unbufferedSegmentLength, $bufferLength = 0)
    {
        // Step 1: calculate non-overlapping segments, allowing the first and the last segments
        //         to be longer than $unbufferedSegmentLength by $bufferLength
        $unbufferedSegments = array();
        for ($offset = 0; $offset < $total; ) {
            $allowedSegmentLength = $unbufferedSegmentLength;
            if ($offset === 0) {
                $allowedSegmentLength += $bufferLength;
            }
            if ($offset + $allowedSegmentLength >= $total) {
                $allowedSegmentLength += $bufferLength;
            }
            $unbufferedSegments[] = min($allowedSegmentLength, $total - $offset);
            $offset += $allowedSegmentLength;
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
        foreach ($unbufferedSegments as $i => $unbufferedSegmentLength) {
            $segOffset = $nextOffset;
            $nextOffset += $unbufferedSegmentLength;
            $discard = array(0, 0);
            if ($i > 0) {
                $unbufferedSegmentLength += $bufferLength;
                $segOffset -= $bufferLength;
                $discard[0] = $bufferLength;
            }
            if ($i < $lastIndex) {
                $unbufferedSegmentLength += $bufferLength;
                $discard[1] = $bufferLength;
            }
            $bufferedSegments[] = array(
                'offset' => $segOffset,
                'length' => $unbufferedSegmentLength,
                // array of two lengths (~left and right) that represent the buffer
                'discard' => $discard,
            );
        }
        return $bufferedSegments;
    }
}
