<?php

/**
 * Layer service interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface LayerServiceInterface {
	/**
	 * Return list of classes implementing LayerInterface.
	 * The class name should be fully qualified.
	 *
	 * @return array $layerClasses
	 */
	public function getLayerClasses();
}

