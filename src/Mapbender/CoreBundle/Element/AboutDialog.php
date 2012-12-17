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
    
     public static function getDefaultConfiguration() {
        return array(
            "tooltip" => "About");
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
                $about = $this->container->get('templating')
                    ->render('MapbenderCoreBundle:Element:about_dialog_content.html.twig');

                $response->setContent($about);
                return $response;
        }
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:about_dialog.html.twig',
                array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
    }
}

