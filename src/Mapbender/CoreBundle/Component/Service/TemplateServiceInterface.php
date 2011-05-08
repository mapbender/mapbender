<?php

/**
 * Template service interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface TemplateServiceInterface {
	/**
	 * Return list of classes implementing TemplateInterface.
	 * The class name should be fully qualified.
	 *
	 * @return array $templateClasses
	 */
	public function getTemplateClasses();
}

