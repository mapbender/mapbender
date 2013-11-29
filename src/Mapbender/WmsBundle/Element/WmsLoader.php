<?php
namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * WmsLoader
 * 
 * @author Karim Malhas
 * @author Paul Schmidt
 */
class WmsLoader extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "WmsLoader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Opens a dialog in which a WMS can be loaded via the getCapabilities-Request";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("wms", "loader");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "",
            "target" => null,
            "autoOpen" => false,
            "defaultFormat" => "image/png",
            "defaultInfoFormat" => "text/html",
            "splitLayers" => false,
            "useDeclarative" => false
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbWmsloader';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        $files = array('js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                'mapbender.element.wmsloader.js'
            ), 'css' => array());
        $config = $this->getConfiguration();
        if (isset($config['useDeclarative']) && $config['useDeclarative'] === true) {
            $files['js'][] = "@MapbenderCoreBundle/Resources/public/mapbender.distpatcher.js";
        }
        return $files;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmsBundle\Element\Type\WmsLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmsBundle:ElementAdmin:wmsloader.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderWmsBundle:Element:wmsloader.html.twig',
                    array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
    }

}
