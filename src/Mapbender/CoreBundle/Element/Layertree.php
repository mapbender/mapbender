<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

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
    static public function listAssets()
    {
        $assets = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/dragdealer.min.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js',
                '@MapbenderCoreBundle/Resources/public/vendor/joii.min.js',
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                'mapbender.element.layertree.tree.js',
                'mapbender.metadata.js'),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/layertree.scss'),
            'trans' => array(
                'MapbenderCoreBundle:Element:layertree.json.twig')
        );
        return $assets;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        $configuration['menu'] = array_values($configuration['menu']);
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    static public function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "type" => null,
            "displaytype" => null,
            "useAccordion" => false,
            "titlemaxlength" => intval(20),
            "autoOpen" => false,
            "showBaseSource" => true,
            "showHeader" => false,
            "menu" => array()
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
                'configuration' => $this->getConfiguration(),
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
