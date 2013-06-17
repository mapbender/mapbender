<?php

namespace Mapbender\WmcBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

/**
 * 
 */
class SuggestMap extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Suggest Map";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Suggest Map";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('Suggest', 'Map');
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                'mapbender.element.suggestmap.js'),
            'css' => array());
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "title" => "Suggest Map",
            "tooltip" => "Suggest Map",
            "target" => null,
            'receiver' => array("email"));
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmcBundle\Element\Type\SuggestMapAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmcBundle:ElementAdmin:suggestmap.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbSuggestMap';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $conf = $this->getConfiguration();
        return $this->container->get('templating')
                        ->render('MapbenderWmcBundle:Element:suggestmap.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
//    public function httpAction($action)
//    {
//        $response = new Response();
//        switch($action)
//        {
//            case 'about':
//                $about = $this->container->get('templating')
//                        ->render('MapbenderCoreBundle:Element:suggestmap.html.twig');
//                $response->setContent($about);
//                return $response;
//        }
//    }
}

