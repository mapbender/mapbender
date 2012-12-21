<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * A Copyright
 * 
 * Displays a copyright label and terms of use.
 * 
 * @author Paul Schmidt
 */
class Copyright extends Element {
    
    public static function getClassTitle() {
        return "Copyright";
    }

    public static function getClassDescription() {
        return "The copyright shows a copyright label and terms of use as a dialog.";
    }

    public static function getClassTags() {
        return array('copyright', 'terms of use');
    }
    
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\CopyrightAdminType';
    }

    public function getAssets() {
        return array(
            'js' => array(
                'mapbender.element.copyright.js'
            ),
            'css' => array(
                'mapbender.elements.css'
            )
        );
    }

    public static function getDefaultConfiguration() {
        return array(
            'tooltip' => 'Copyright',
            "copyrigh_text" => "© XXX, 2012",
            "dialog_link" => "Terms of use",
            "dialog_content" => "Terms of use (Content)",
            "dialog_title" => "Terms of use");
    }

    public function getWidgetName() {
        return 'mapbender.mbCopyright';
    }

    public function render() {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:copyright.html.twig',
                        array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }
}

