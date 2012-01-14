<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class FeatureInfo extends Element implements ElementInterface {
	static public function getTitle() {
		return "FeatureInfo";
	}

	static public function getDescription() {
		return "Renders a button to trigger a feature info request and popup";
	}

	static public function getTags() {
		return array('Button', 'FeatureInfo');
	}

	public function getAssets() {
		return array(
            'js' => array(
                'mapbender.element.button.js', //Our base widget
                'mapbender.element.featureInfo.js'
            ),
            'css' => array(
                'mapbender.elements.css'
            )
		);
	}

	public function getConfiguration() {
        $opts = $this->configuration;
        $elementId = $this->configuration['target'];
        $finalId = $this->application->getFinalId($elementId);
        $opts = array_merge($opts, array('target' => $finalId));

        return array(
            'options' => $opts,
			'init' => 'mbFeatureInfo',
		);
    }

	public function render() {
            return $this->get('templating')->render('MapbenderCoreBundle:Element:button.html.twig', array(
                'id' => $this->id,
                'configuration' => array_merge($this->configuration),
                'label' => $this->configuration['title']));
	}
}

