<?php


namespace Mapbender\PrintBundle\Component\Legend;

/**
 * @todo: add fit-to-region calculation method to this interface
 */
interface LegendBlockContainer
{
    /**
     * @return bool
     */
    public function isRendered();

    /**
     * @return LegendBlock[]
     */
    public function getBlocks();
}
