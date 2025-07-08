<?php


namespace Mapbender\PrintBundle\Component;


use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;

/**
 * The service responsible for rendering this data source to a canvas, mainly for print and image export
 */
abstract class LayerRenderer
{
    /**
     * Should render the image modeled by the given $layerDef array onto the
     * given $canvas.
     */
    abstract public function addLayer(ExportCanvas $canvas, array $layerDef, Box $extent): void;

    /**
     * Receives two array-formatted rendering layer definitions. If a more
     * efficient single layer definition exists, this method should create
     * and return it. Otherwise it should return false.
     */
    abstract public function squashLayerDefinitions(array $layerDef, array $nextLayerDef, Resolution $resolution): array|false;
}
