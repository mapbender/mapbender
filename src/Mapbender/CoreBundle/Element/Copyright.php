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
class Copyright extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "Copyright";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "The copyright shows a copyright label and terms of use as a dialog.";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array('copyright', 'terms of use');
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\CopyrightAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.copyright.js'
            ),
            'css' => array(
                'mapbender.element.copyright.css'
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'Copyright',
            "copyrigh_text" => "© XXX, 2012",
            "copyright_link" => "Terms of use",
            "link_type" => "",
            "link_url" => null,
            "dialog_content" => "Terms of use (Content)",
            "dialog_title" => "Terms of use",
            'width' => "200px",
            'anchor' => 'left-bottom',
            'position' => array('0px', '0px'));
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbCopyright';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:copyright.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

}

