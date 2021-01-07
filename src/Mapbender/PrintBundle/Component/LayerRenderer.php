<?php


namespace Mapbender\PrintBundle\Component;


use Mapbender\PrintBundle\Component\Export\Box;
use Mapbender\PrintBundle\Component\Export\ExportCanvas;
use Mapbender\PrintBundle\Component\Export\Resolution;

abstract class LayerRenderer
{
    /**
     * Should render the image modeled by the given $layerDef array onto the
     * given $canvas.
     *
     * @param ExportCanvas $canvas
     * @param array $layerDef
     * @param Box $extent
     */
    abstract public function addLayer(ExportCanvas $canvas, $layerDef, Box $extent);

    /**
     * Receives two array-formatted rendering layer definitions. If a more
     * efficient single layer definition exists, this method should create
     * and return it. Otherwise it should return false.
     *
     * @param $layerDef
     * @param $nextLayerDef
     * @param Resolution $resolution
     * @return array|false
     */
    abstract public function squashLayerDefinitions($layerDef, $nextLayerDef, $resolution);
}
