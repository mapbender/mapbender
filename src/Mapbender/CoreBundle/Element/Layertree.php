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
        $assets =  array('js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js'),
            'css' => array()
        );
        $config = parent::getConfiguration();
        if(true) //@TODO 
            $assets["js"][] = 'mapbender.element.layertree.tree.js';
        else if(isset($config["displaytype"]) && $config["displaytype"] === "list")
            $assets["js"][] = 'mapbender.element.layertree.list.js';
        else if(isset($config["displaytype"]) && $config["displaytype"] === "tree")
            $assets["js"][] = 'mapbender.element.layertree.tree.js';
        return $assets;
    }

    /**
     * @inheritdoc
     */
    static public function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "layerMenu" => true,
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

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:layer_tree.html.twig';
    }

}

