<?php

namespace Mapbender\WmcBundle\Component;

/**
 * @author Paul Schmidt
 */
class MapbenderWMC
{
    public $wmc_json;
    public $sources_json;

    public function explodeWmcLayers()
    {
        $wmc = new MapbenderWMC();
        $wmc->wmc_json = array(
            "id" => clone $this->$wmc_json["id"],
            "version" => clone $this->$wmc_json["version"],
            "general" => clone $this->$wmc_json["general"],
            "layerlist" => array()
        );
        foreach ($this->wmc_json["layerlist"] as $layer) {
            $names = explode(",", $layer->name);
            if (count($names) > 1) {
                foreach ($names as $name) {
                    $layerDef = clone $layer;
                    $layerDef["name"] = $name;
                    $wmc->wmc_json["layerlist"][] = $layerDef;
                }
            }
        }// @TODO legend, style
        return $wmc;
    }
}
