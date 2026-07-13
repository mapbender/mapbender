<?php

namespace Mapbender\CoreBundle\Component\Source;

class BaseSourceLoaderSettings implements SourceLoaderSettings
{
    public function __construct(private readonly bool $activateNewLayers, private readonly bool $selectNewLayers)
    {
    }

    public function activateNewLayers(): bool
    {
        return $this->activateNewLayers;
    }

    public function selectNewLayers(): bool
    {
        return $this->selectNewLayers;
    }
}
