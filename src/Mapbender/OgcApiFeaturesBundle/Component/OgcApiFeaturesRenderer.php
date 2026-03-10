<?php

namespace Mapbender\OgcApiFeaturesBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Mapbender\PrintBundle\Component\LayerRenderer;

class OgcApiFeaturesRenderer extends LayerRenderer
{
    public function addLayer(ExportCanvas $canvas, array $layerDef, Box $extent, array $jobData): void
    {
        // No need for implementation so far - print works
    }

    public function squashLayerDefinitions(array $layerDef, array $nextLayerDef, Resolution $resolution): array|false
    {
        // No need for implementation so far - print works
    }
}
