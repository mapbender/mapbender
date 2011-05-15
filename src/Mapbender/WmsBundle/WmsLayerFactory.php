<?php

namespace Mapbender\WmsBundle;

use Mapbender\CoreBundle\Component\LayerFactoryInterface;
use Mapbender\WmsBundle\WmsLayer;

class WmsLayerFactory implements LayerFactoryInterface {
	public function getLayerClass() {
		return "wms";
	}

	public function create($name, array $configuration) {
		return new WmsLayer($name, $configuration);
	}
}

