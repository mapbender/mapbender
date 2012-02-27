<?php

namespace Mapbender\WmtsBundle;

use Mapbender\CoreBundle\Component\LayerFactoryInterface;
use Mapbender\WmtsBundle\WmtsLayerLoader;

class WmtsLayerFactory implements LayerFactoryInterface {
	public function getLayerClass() {
		return "wmts";
	}

	public function create($name, array $configuration) {
		return new WmtsLayeLoader($name, $configuration);
	}
}

