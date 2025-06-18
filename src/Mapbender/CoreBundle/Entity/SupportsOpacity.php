<?php

namespace Mapbender\CoreBundle\Entity;

/**
 * Should be implemented by @see SourceInstance s that support changing the layer opacity.
 */
interface SupportsOpacity
{
    public function getOpacity(): int;

    public function setOpacity(int $opacity): self;
}
