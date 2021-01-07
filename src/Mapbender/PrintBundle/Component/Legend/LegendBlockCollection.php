<?php


namespace Mapbender\PrintBundle\Component\Legend;


abstract class LegendBlockCollection implements LegendBlockContainer
{
    /**
     * @return \Iterator|LegendBlock[]
     */
    abstract public function iterateBlocks();

    abstract public function clear();

    /**
     * @return LegendBlock[]
     */
    public function getBlocks()
    {
        return iterator_to_array($this->iterateBlocks());
    }

    /**
     * Returns true if all contained blocks have already been marked as rendered.
     *
     * @return bool
     */
    public function isRendered()
    {
        foreach ($this->iterateBlocks() as $block) {
            if (!$block->isRendered()) {
                return false;
            }
        }
        return true;
    }
}
