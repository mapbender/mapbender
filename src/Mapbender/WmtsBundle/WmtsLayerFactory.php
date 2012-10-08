<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\Layer;
use Mapbender\WmtsBundle\WmtsLayerLoader;

class WmtsLayerFactory extends Layer {
	public function getLayerClass() {
		return "wmts";
	}

    public function getType(){
        return "wmts";
    }

	public function create($name, array $configuration) {
		return new WmtsLayeLoader($name, $configuration);
	}
}

