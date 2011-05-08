<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Basic <div> element. More for demo purposes, consider using a
 * custom template instead.
 *
 * @author Christian Wygoda <arsgeografica@gmail.com>
 */
class DivElement implements ElementInterface {
	private $templating;

	public function __construct(EngineInterface $templating) {
		$this->templating = $templating
	}

	public function getTitle() {
		return 'HTML div';
	}

	public function getDescription() {
		return 'Renders a HTML div element.'
	}

	public function getTags() {
		return array('HTML');
	}

	public function getAssets() {
		return NULL;
	}

	public function getParents() {
		// Returning NULL means: all parents allowed
		return NULL;
	}

	public function isContainer() {
		return true;
	}

	public function render(ElementInterface parentElement = NULL, $block = 'content') {
		switch($block) {
			case 'title':
				return $this->getTitle();
				break;
			case 'content':
				return $templating->render('MapbenderCoreBundle:Element:div.html.twig',
					array('attributes' => NULL,
						'content' => '',
						'children' => NULL));
		}
	}
}

