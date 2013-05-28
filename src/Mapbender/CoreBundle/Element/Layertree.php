<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * 
 */
class Layertree extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Layertree";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Tree of map's layers";
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return "Shows a treeview of the layers on the map";
    }

    /**
     * @inheritdoc
     */
    public function getTags()
    {
        return array('Layertree', 'Layer');
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbLayertree';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LayertreeAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.layertree.js'
            ),
            'css' => array(
                'mapbender.element.layertree.css'
            )
        );
    }

    /**
     * @inheritdoc
     */
    static public function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "layerMenu" => false,
            "layerRemove" => true,
            "type" => null,
            "displaytype" => null,
            "useAccordion" => false,
            "titlemaxlength" => intval(20),
            "autoOpen" => false,
            "showBaseSource" => true,
            "showHeader" => false,
        );
    }

//    public function httpAction($action) {
//        $response = new Response();
//
//        $data = array(
//            'message' => 'Hello World'
//        );
//        $response->setContent(json_encode($data));
//        $response->headers->set('Content-Type', 'application/json');
//        return $response;
//    }
    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
                        'MapbenderCoreBundle:Element:layertree.html.twig',
                        array(
                    'id' => $this->getId(),
                    'configuration' => $this->entity->getConfiguration(),
                    'title' => $this->getTitle()
                        )
        );
    }

}

