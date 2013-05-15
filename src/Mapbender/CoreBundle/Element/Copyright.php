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
            "copyright_text" => "© Mapbender3, " . date("Y"),
            "dialog_link" => "Terms of use",
            "dialog_content" => "Terms of use (Content)",
            "dialog_title" => "Terms of use",
            'width' => "200px",
            'anchor' => 'right-bottom',
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

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:copyright.html.twig';
    }
}

