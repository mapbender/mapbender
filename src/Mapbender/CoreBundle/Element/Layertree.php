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
        return "mb.core.layertree.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.core.layertree.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getTags()
    {
        return array(
            "mb.core.layertree.tag.layertree",
            "mb.core.layertree.tag.layer",
            "mb.core.layertree.tag.tree");
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
        $assets = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js'),
            'css' => array(),
            'trans' => array('MapbenderCoreBundle:Element:layertree.json.twig')
        );
        $config = parent::getConfiguration();
        if (true) //@TODO 
                $assets["js"][] = 'mapbender.element.layertree.tree.js';
        else if (isset($config["displaytype"]) && $config["displaytype"] === "list")
                $assets["js"][] = 'mapbender.element.layertree.list.js';
        else if (isset($config["displaytype"]) && $config["displaytype"] === "tree")
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
            "layerRemove" => true,
            "type" => null,
            "displaytype" => null,
            "useAccordion" => false,
            "titlemaxlength" => intval(20),
            "autoOpen" => false,
            "showBaseSource" => true,
            "showHeader" => false,
            "menu" => null
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $config = $this->entity->getConfiguration();
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
        return 'MapbenderCoreBundle:ElementAdmin:layertree.html.twig';
    }

}
