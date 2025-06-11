<?php

namespace Mapbender\VectorTilesBundle\Component;

use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;
use Mapbender\PrintBundle\Component\LayerRenderer;

class VectorTilesRenderer extends LayerRenderer
{

    public function addLayer(ExportCanvas $canvas, array $layerDef, Box $extent): void
    {

    }

    public function squashLayerDefinitions(array $layerDef, array $nextLayerDef, Resolution $resolution): array|false
    {
        return false;
    }
}
