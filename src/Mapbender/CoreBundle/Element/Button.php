<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Button extends Element implements ElementInterface {
	public function getTitle() {
		return "Button";
	}

	public function getDescription() {
		return "Renders a Button";
	}

	public function getTags() {
		return array('button');
	}

	public function getAssets() {
		return array(
            'js' => array(
                'bundles/mapbendercore/Mapbender.Element.Button.js'
            ), // jquery.ui.button.js
			'css' => array()
		);
	}

	public function getConfiguration() {
        $opts = $this->configuration;
        if(array_key_exists('target', $this->configuration)) {
            $elementId = $this->configuration['target'];
            $finalId = $this->application->getFinalId($elementId);
            $opts = array_merge($opts, array('target' => $finalId));
        }
        return array(
            'options' => $opts,
			'init' => 'mb_button',
		);
	}

	public function	render($widget, $block = 'content', ElementInterface $parentElement = NULL) {
            return $this->get('templating')->render('MapbenderCoreBundle:Element:button.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration));
	}
}

