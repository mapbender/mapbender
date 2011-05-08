<?php

namespace Mapbender\CoreBundle\Component;

/**
 * Layer interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface LayerInterface {
	/**
	 * Instantiate from WMC fragment
	 * TODO: Type fo $fragment - string? some XML class?
	 * 
	 * @param WMC fragment
	 */
	public static function loadFromWMC($fragment);

	/**
	 * Save to WMC fragment
	 * TODO: Type of $fragment - string? some XML class?
	 * 
	 * @return WMC fragment
	 */
	public function saveToWMC();

	/**
	 * Render to output for given type. Default is rendering
	 * the Javascript for use with MapQuery.
	 * 
	 * @param string $type
	 */
	public function render($type = 'MapQuery');

	//TODO: Configuration: CRUD + Form
}

