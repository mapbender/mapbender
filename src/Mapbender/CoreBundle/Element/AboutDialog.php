<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

class AboutDialog extends Element {
    static public function getClassTitle() {
        return "About dialog";
    }

    static public function getClassDescription() {
        return "Renders a button to show a about dialog";
    }

    static public function getClassTags() {
        return array('Help', 'Info', 'About');
    }

    public function getWidgetName() {
        return 'mapbender.mbAboutDialog';
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.button.js',
                'mapbender.element.aboutDialog.js'),
            'css' => array());
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
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:about_dialog.html.twig',
                array(
                    'id' => $this->getId(),
                    'configuration' => $this->getConfiguration()));
    }
}

