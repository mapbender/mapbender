<?php

namespace Mapbender\Component;

class BaseSourceLoaderSettings implements SourceLoaderSettings
{
    private bool $activateNewLayers;
    private bool $selectNewLayers;

    public function __construct($activateNewLayers, $selectNewLayers)
    {
        $this->activateNewLayers = $activateNewLayers;
        $this->selectNewLayers = $selectNewLayers;
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
