<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

/**
 * Template interface
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
interface TemplateInterface {
	/**
	 * Constructor, which receives an templatin service
	 *
	 * @param EngineInterface $templating
	 */
	public function __construct(EngineInterface $templating);

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

	/**
	 * Render the template with the given data
	 *
	 * @param array $data
	 * @return string The evaluated template as a string
	 */
	public function render($data);
}

