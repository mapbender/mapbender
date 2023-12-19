<?php

namespace Mapbender\Component;

interface SourceLoaderSettings
{
    public function activateNewLayers(): bool;
    public function selectNewLayers(): bool;
}
