<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class AboutDialog extends Element implements ElementInterface {
	public function getTitle() {
		return "About dialog";
	}

	public function getDescription() {
		return "Renders a button to show a about dialog";
	}

	public function getTags() {
		return array('button', 'about');
	}

	public function getAssets() {
		return array(
            'js' => array(
                'mapbender.element.button.js',
                'mapbender.element.aboutDialog.js'
            ),
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
			'init' => 'mb_about_dialog',
		);
    }

    public function httpAction($action) {
        $response = new Response();
        switch($action) {
            case 'about':
                $user = $this->get('security.context')->getToken()->getUser();
                if($user instanceof UserInterface) {
                    $username = $user->getUsername();
                } else {
                    $username = $user;
                }

                $about = array(
                    'version' => '3.0 alpha',
                    'user' => $username
                );

                $response->setContent(json_encode($about));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
        }
    }

	public function render() {
            return $this->get('templating')->render('MapbenderCoreBundle:Element:about_dialog.html.twig', array(
                'id' => $this->id,
                'configuration' => $this->configuration));
	}
}

