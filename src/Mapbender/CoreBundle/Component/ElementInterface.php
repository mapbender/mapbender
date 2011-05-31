<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Element interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface ElementInterface {
	/**
	 * Constructor
	 *
	 * @param string $id The id which will be used for the element
	 * @param string $name The element name
	 * @param array $configuration The element configuration array
     * @param Application $application The surrounding application
	 */
	public function __construct($id, $name, array $configuration, $application);

	/**
	 * Return element title
	 *
	 * @return string $title
	 */
	public function getTitle();

	/**
	 * Return element description.
	 *
	 * @return string $title
	 */
	public function getDescription();

	/**
	 * Return tags for searching
	 *
	 * @return array $tags
	 */
	public function getTags();

	/**
	 * Return array of assets (CSS, JS).
	 * These will be included by the application.
	 * array(
	 *   'js' => array(
	 *     'js/fileA.js',
	 *     'js/fileB.js',
	 *   ),
	 *   'css' => array(
	 *     'css/fileA.css',
	 *     'css/fileB.css',
	 *   );
	 *
	 * @return array $assets
	 */
	public function getAssets();

	/**
	 * Return the list of possible parent elements.
	 * If the list is empty, it is assumed that the 
	 * element can be inserted in any container element.
	 * The list should contain the full class names of 
	 * the possible parent elements.
	 *
	 * @return array $parents
	 */
	public function getParents();

	/**
	 * Return whether this elements is a container
	 * element, e.g. can contain other elements
	 *
	 * @return Boolean $container
	 */
	public function isContainer();

	/**
	 * Return the id of the element
	 *
	 * @return string Id
	 */
	public function getId();

	/**
	 * Return the configuration as an array, ready
	 * for json_encode. This array should contain
	 * an array 'configuration' and an string 'init',
	 * which is the name of the init function (js).
	 * It will be called like this:
	 * Mapbender.element.<init>(<id>, <configuration>)
	 *
	 * @return array Element configuration
	 */
	public function getConfiguration();

	/**
	 * Callback for HTTP calls, usually Ajax calls.
	 * Gets the action name as a parameter.
	 *
	 * @param string $action The action to do
	 * @return HttpResponse HTTP response
	 */
	public function httpAction($action);

	/**
	 * Output the HTML for rendering.
     */
	public function render();

	/**
	 * Elements shall implement __toString as a alias for render()
	 * This allows simpler application templates
	 */
	public function __toString();
}

