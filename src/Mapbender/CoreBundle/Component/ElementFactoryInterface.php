<?php

namespace Mapbender\CoreBundle\Component;

/**
 * Layer factory interface
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
interface ElementFactoryInterface {
	/**
	 * Return an array of element classes provided by
	 * the bundle.
	 *
	 * @return array The element classes array
	 */
	public function getElementClasses();
}

