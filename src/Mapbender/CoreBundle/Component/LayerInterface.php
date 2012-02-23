<?php

namespace Mapbender\CoreBundle\Component;

/**
 * Layer interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface LayerInterface {
	/**
	 * Constructor
	 *
	 * @param string $title The layer id
	 * @param array $configuration The layer configuration
	 */
	public function __construct($layerSetId, $layerId, array $configuration, $doctrine = null);

	/**
	 * Return an array representation of the layer title and
	 * configuration ready for json_encode.
	 *
	 * @param string $type
	 * @return string JSONified configuration
	 */
	public function render();

    /**
     * Return assets.
     *
     * @return array Assets array
     */
    public function getAssets();

    //TODO: Configuration: Form, store / load at runtime
}

