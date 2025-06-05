<?php

namespace Mapbender\CoreBundle\Component\Source;

interface SourceLoaderSettings
{
    public function activateNewLayers(): bool;
    public function selectNewLayers(): bool;
}
