<?php

namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\TemplateInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class Fullscreen implements TemplateInterface {
	private $templating;

	public function __construct(EngineInterface $templating) {
		$this->templating = $templating;
	}

	public function getTemplate($_format) {
		return 'MapbenderCoreBundle:Template:fullscreen.html.twig';
	}

	public function getMetadata() {
		return array(
			'type' => 'application',
			'regions' => array('top', 'content'),
			'css' => array('mapbender.template.fullscreen.css'),
			'js' => array(),
		);
	}

	public function render($data, $parts, $_format = 'html') {
		return $this->templating->render($this->getTemplate($_format), $data);
	}
}

