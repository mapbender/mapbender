<?php

/**
 * ElementService Interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */

ElementServiceInterface {
	/**
	 * Return list of classes implementing ElementInterface.
	 * The class name should be fully qualified.
	 *
	 * @return array $elementClasses
	 */
	public function getElementClasses();
}

