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
     * @param String $_format The requested template format
	 * @return Application template
	 */
	function getTemplate();

	/**
	 * Return the array of layersets.
	 *
	 * @param string $layerset The layerset for which to return layers
	 * @return array Application layersets
	 */
	function getLayersets();

	/**
	 * Return the element specified by id.
	 *
	 * @param string $id The element id
	 * @return ElementInterface The element
	 */
	function getElement($id);

	/**
	 * Render the application
     *
     * @param Array $parts The requested parts. Can be a combination if 'css', 'html', 'js' and 'configuration'
     * @param String $format The required response format, defaults to html
	 * @return Response A Response instance
	 */
	function render($parts = array('css', 'html', 'js', 'javascript'), $_format = 'html');
}

