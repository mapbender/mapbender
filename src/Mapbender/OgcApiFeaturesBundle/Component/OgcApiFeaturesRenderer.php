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
        // No need for implementation so far
        // the features are added as an OpenLayers VectorSource, which is automatically added to the print via MbImageExport::_collectGeometryLayers
    }

    public function squashLayerDefinitions(array $layerDef, array $nextLayerDef, Resolution $resolution): array|false
    {
        // No need for implementation so far
        // the features are added as an OpenLayers VectorSource, which is automatically added to the print via MbImageExport::_collectGeometryLayers
    }
}
