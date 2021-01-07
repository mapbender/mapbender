<?php


namespace Mapbender\PrintBundle\Component\Export;

/**
 * Models a line section with a buffer around it.
 */
class BufferedSection
{
    protected $offset;
    protected $innerLength;
    protected $bufferBefore;
    protected $bufferAfter;

    /**
     * @param float|int $offset
     * @param float|int $innerLength
     * @param float|int $bufferBefore
     * @param float|int $bufferAfter
     */
    public function __construct($offset, $innerLength, $bufferBefore = 0, $bufferAfter = 0)
    {
        $this->offset = $offset;
        $this->innerLength = $innerLength;
        $this->bufferBefore = $bufferBefore;
        $this->bufferAfter = $bufferAfter;
    }

    /**
     * @return float|int
     */
    public function getBufferedOffset()
    {
        return $this->offset - $this->bufferBefore;
    }

    /**
     * @return float|int
     */
    public function getUnbufferedOffset()
    {
        return $this->offset;
    }

    /**
     * @return float|int
     */
    public function getBufferedLength()
    {
        return $this->innerLength + $this->bufferBefore + $this->bufferAfter;
    }

    /**
     * @return float|int
     */
    public function getUnbufferedLength()
    {
        return $this->innerLength;
    }

    /**
     * @return float|int
     */
    public function getBufferedEnd()
    {
        return $this->offset + $this->innerLength + $this->bufferAfter;
    }

    /**
     * @return float|int
     */
    public function getUnbufferedEnd()
    {
        return $this->getBufferedEnd() - $this->bufferAfter;
    }

    /**
     * @return float|int
     */
    public function getBufferBefore()
    {
        return $this->bufferBefore;
    }

    /**
     * @return float|int
     */
    public function getBufferAfter()
    {
        return $this->bufferAfter;
    }
}
