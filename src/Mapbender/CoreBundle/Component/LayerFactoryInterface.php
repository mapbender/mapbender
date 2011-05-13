<?php

namespace Mapbender\CoreBundle\Component;

/**
 * Layer factory interface
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
interface LayerFactoryInterface {
	/**
	 * Returns the machine-readable identifier for the layer class.
	 * This is somethin like "wms".
	 *
	 * @return string The layer class identifier
	 */
	public function getLayerClass();

	/**
	 * Factory. Given the configuration, it instantiates
	 * a layer object
	 *
	 * @param string $name The layer name
	 * @param array $configuration The layer configuration
	 * @return LayerInterface The layer
	 */
	public function create($name, array $configuration);
}

