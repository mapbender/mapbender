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
	 * @param string $name The layer name
	 * @param array $configuration The layer configuration
	 * @param string $srs The Application SRS
	 */
	public function __construct(string $name, array $configuration, string $srs);

	/**
	 * Render to output for given type. Default is rendering
	 * the Javascript for use with MapQuery.
	 * 
	 * @param string $type
	 */
	public function render($type = 'MapQuery');

	//TODO: Configuration: Form, store / load at runtime
}

