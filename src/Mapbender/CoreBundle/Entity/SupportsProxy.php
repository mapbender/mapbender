<?php

namespace Mapbender\CoreBundle\Entity;

/**
 * Should be implemented by @see SourceInstance s that support requests being sent over Mapbender's proxy.
 */
interface SupportsProxy
{
    public function getProxy(): bool;

    public function setProxy(bool $proxy): self;
}
