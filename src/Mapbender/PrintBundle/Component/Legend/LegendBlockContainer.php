<?php


namespace Mapbender\PrintBundle\Component\Legend;


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
