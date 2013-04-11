<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

/**
 * 
 */
class AboutDialog extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "About Dialog";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Renders a button to show a about dialog";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('Help', 'Info', 'About');
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.button.js',
                'mapbender.element.aboutDialog.js'),
            'css' => array());
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "About",
            'label' => true,
            'icon' => 'abouticon');
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\AboutDialogAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:about_dialog.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbAboutDialog';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:about_dialog.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        $response = new Response();
        switch($action)
        {
            case 'about':
                $about = $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:about_dialog_content.html.twig');
                $response->setContent($about);
                return $response;
        }
    }
}

