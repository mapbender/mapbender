<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\Resolution;

/**
 * Models something that has a width and a height in pixel space
 */
abstract class BaseCanvas
{
    /**
     * @return int
     */
    abstract public function getWidth();

    /**
     * @return int
     */
    abstract public function getHeight();

    /**
     * @param Box $extent
     * @return Resolution
     */
    public function getResolution(Box $extent)
    {
        $h = $extent->getWidth() / $this->getWidth();
        $v = $extent->getHeight() / $this->getHeight();
        return new Resolution($h, $v);
    }
}
