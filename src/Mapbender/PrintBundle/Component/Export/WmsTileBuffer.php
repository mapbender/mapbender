<?php


namespace Mapbender\PrintBundle\Component\Export;


/**
 * Models "extra" pixels around the four sides of a WMS request that can be
 * discarded later.
 *
 * Implemented as arivial aliasing of Box, because that already gives us
 * convenient top / bottom / left / right properties.
 */
class WmsTileBuffer extends Box
{
}
