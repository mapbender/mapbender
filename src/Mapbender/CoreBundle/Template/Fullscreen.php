<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\TemplateInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class Fullscreen implements TemplateInterface {
	private $templating;

	public function __construct(EngineInterface $templating) {
		$this->templating = $templating;
	}

	public function getTemplate() {
		return 'MapbenderCoreBundle:Template:fullscreen.html.twig';
	}

	public function getMetadata() {
		return array(
			'type' => 'application',
			'regions' => array('top', 'content'),
			'css' => array('bundles/mapbendercore/fullscreen.css'),
			'js' => array(),
		);
	}

	public function render($data) {
		return $this->templating->render($this->getTemplate(), $data);
	}
}

