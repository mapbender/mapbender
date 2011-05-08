<?php

namespace Mapbender\CoreBundle\Component;

/**
 * Element interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface ElementInterface {
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
	 * Output the HTML for rendering. The function
	 * receives a reference to the parent element and
	 * a name of a block to render which defaults to
	 * 'content', 'title' can also be given.
	 *
	 * @param ElementInterface $parent
	 * @param string $block
	 * @return string $html
	 */
	public function render(ElementInterface parentElement = NULL, $block = 'content');
}

