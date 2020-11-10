<?php


namespace Mapbender\PrintBundle\Component\Export;


class WmsTile
{
    /** @var Box */
    protected $offsetBox;
    /** @var WmsTileBuffer */
    protected $buffer;

    /**
     * @param Box $offsetBox
     * @param WmsTileBuffer $buffer
     */
    public function __construct(Box $offsetBox, WmsTileBuffer $buffer)
    {
        $this->offsetBox = $offsetBox;
        $this->buffer = $buffer;
    }

    /**
     * @param BufferedSection $horizontal
     * @param BufferedSection $vertical
     * @return static
     */
    public static function fromSections($horizontal, $vertical)
    {
        $offsetBox = new Box($horizontal->getBufferedOffset(), $vertical->getBufferedOffset(),
                             $horizontal->getBufferedEnd(), $vertical->getBufferedEnd());
        $buffer = new WmsTileBuffer($horizontal->getBufferBefore(), $vertical->getBufferBefore(),
                                    $horizontal->getBufferAfter(), $vertical->getBufferAfter());
        return new static($offsetBox, $buffer);
    }

    /**
     * @return Box
     */
    public function getOffsetBox()
    {
        return $this->offsetBox;
    }

    /**
     * @return WmsTileBuffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Return an extent box covering this tile, including any buffer regions around it.
     *
     * @param Box $fullExtent
     * @param $fullWidth
     * @param $fullHeight
     * @return Box
     */
    public function getExtent(Box $fullExtent, $fullWidth, $fullHeight)
    {
        $resolution = array(
            'h' => $fullExtent->getWidth() / $fullWidth,
            'v' => $fullExtent->getHeight() / $fullHeight,
        );

        $x0 = $resolution['h'] * $this->offsetBox->left + $fullExtent->left;
        $x1 = $resolution['h'] * $this->offsetBox->right + $fullExtent->left;
        $y0 = $resolution['v'] * $this->offsetBox->bottom + $fullExtent->bottom;
        $y1 = $resolution['v'] * $this->offsetBox->top + $fullExtent->bottom;

        return new Box($x0, $y0, $x1, $y1);
    }

    public function getWidth($includeBuffer)
    {
        if ($includeBuffer) {
            return intval(abs($this->offsetBox->getWidth()));
        } else {
            return $this->getWidth(true) - intval($this->buffer->left + $this->buffer->right);
        }
    }

    public function getHeight($includeBuffer)
    {
        if ($includeBuffer) {
            return intval(abs($this->offsetBox->getHeight()));
        } else {
            return $this->getHeight(true) - intval($this->buffer->bottom + $this->buffer->top);
        }
    }
}
