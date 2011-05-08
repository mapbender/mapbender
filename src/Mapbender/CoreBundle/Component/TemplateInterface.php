<?php

/**
 * Template interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface TemplateInterface {
	/**
	 * Return template specifier like
	 * "MapbenderCoreBundle:Application:demo1"
	 *
	 * @return string $templateId
	 */
	public function getTemplate();

	/**
	 * Return template metadata
	 * This includes type (application, element, etc.) and
	 * type-specific information, like list of regions for
	 * application templates.
	 * array(
	 *   'type' => 'application',
	 *   'regions' => array('toolbar', 'sidebar', 'content', 'footer'),
	 * )
	 *
	 * @return array $metadata
	 */
	public function getMetadata();
}

