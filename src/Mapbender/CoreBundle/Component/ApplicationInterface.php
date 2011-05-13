<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Response;

/**
 * Application interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface ApplicationInterface {
	/**
	 * Return the human-readable application title
	 *
	 * @return string Application title
	 */
	function getTitle();

	/**
	 * Return the human-readable application description
	 *
	 * @return string Application description
	 */
	function getDescription();

	/**
	 * Return the class name of the Component\TemplateInterface to use
	 *
	 * @return Application template
	 */
	function getTemplate();

	/**
	 * Return the array of elements. Each element has a class and id
	 *
	 * @return array Application element tree
	 */
	function getElements();

	/**
	 * Return the array of layers. Each layer has a class and id
	 *
	 * @return array Application layers
	 */
	function getLayers();

	/**
	 * Render the application
	 *
	 * @param Response $response A Response instance
	 * @return Response A Response instance
	 */
	function render(Response $response = NULL);
}

