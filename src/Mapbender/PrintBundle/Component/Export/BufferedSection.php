<?php


namespace Mapbender\PrintBundle\Component\Export;

/**
 * Models a line section with a buffer around it.
 */
class BufferedSection
{
    /**
     * @param float|int $offset
     * @param float|int $innerLength
     * @param float|int $bufferBefore
     * @param float|int $bufferAfter
     */
    public function __construct(protected $offset, protected $innerLength, protected $bufferBefore = 0, protected $bufferAfter = 0)
    {
    }

    /**
     * @return float|int
     */
    public function getBufferedOffset(): int|float
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
    public function getBufferedLength(): float|int|array
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
    public function getBufferedEnd(): float|int|array
    {
        return $this->offset + $this->innerLength + $this->bufferAfter;
    }

    /**
     * @return float|int
     */
    public function getUnbufferedEnd(): int|float
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
