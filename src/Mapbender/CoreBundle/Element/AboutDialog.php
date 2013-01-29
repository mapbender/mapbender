<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

class AboutDialog extends Element {
    static public function getClassTitle() {
        return "About Dialog";
    }

    static public function getClassDescription() {
        return "Renders a button to show a about dialog";
    }

    static public function getClassTags() {
        return array('Help', 'Info', 'About');
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.button.js',
                'mapbender.element.aboutDialog.js'),
            'css' => array());
    }
    
    public static function getDefaultConfiguration() {
        return array(
            "tooltip" => "About",
            'label' => true,
            'icon' => 'abouticon');
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\AboutDialogAdminType';
    }

    public function getWidgetName() {
        return 'mapbender.mbAboutDialog';
    }
    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:about_dialog.html.twig',
                array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
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

}

