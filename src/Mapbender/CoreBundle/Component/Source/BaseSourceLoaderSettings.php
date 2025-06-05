<?php

namespace Mapbender\CoreBundle\Component\Source;

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
