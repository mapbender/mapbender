<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * The Legend class shows legends of the map's layers.
 * 
 * @author Paul Schmidt
 */
class Legend extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.legend.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.legend.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.legend.tag.layer",
            "mb.core.legend.tag.legend");
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.legend.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js' ),
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/legend.scss'),
            'trans' => array('MapbenderCoreBundle:Element:legend.json.twig')
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "elementType" => null,
            "displayType" => null,
            "autoOpen" => true,
            "tooltip" => "Legend",
            "checkGraphic" => false,
            "hideEmptyLayers" => true,
            "generateLegendGraphicUrl" => false,
            "showSourceTitle" => true,
            "showLayerTitle" => true,
            "showGrouppedTitle" => true);
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LegendAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbLegend';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
                'MapbenderCoreBundle:Element:legend.html.twig',
                array('id' => $this->getId(),
                "title" => $this->getTitle(),
                'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:legend.html.twig';
    }

}
