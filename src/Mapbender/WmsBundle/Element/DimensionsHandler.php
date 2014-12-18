<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Dimensions handler
 * @author Paul Schmidt
 */
class DimensionsHandler extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.wms.dimhandler.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wms.dimhandler.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("mb.wms.dimhandler.dimension", "mb.wms.dimhandler.handler");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "",
            "target" => null,
            'dimensionsets' => array()
            
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbDimensionsHandler';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $files = array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/vendor/joii.min.js',
                'mapbender.wms.dimension.js',
                'mapbender.element.dimensionshandler.js',
                ),
            'css' => array(
                '@MapbenderWmsBundle/Resources/public/sass/element/dimensionshandler.scss',
                '@MapbenderCoreBundle/Resources/public/sass/element/mbslider.scss'),
            'trans' => array('MapbenderWmsBundle:Element:dimensionshandler.json.twig'));
        return $files;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmsBundle\Element\Type\DimensionsHandlerAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmsBundle:ElementAdmin:dimensionshandler.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderWmsBundle:Element:dimensionshandler.html.twig',
                         array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'configuration' => $this->getConfiguration()
            ));
    }
    
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        foreach ($configuration['dimensionsets'] as $key => &$value) {
            $value['dimension'] = $value['dimension']->getConfiguration();
//            $a = 0;
        }
//        $config = $this->entity->getConfiguration();
//        //@TODO merge recursive $this->entity->getConfiguration() and $this->getDefaultConfiguration()
//        $def_configuration = $this->getDefaultConfiguration();
//        $configuration = array();
//        foreach ($def_configuration as $key => $val) {
//            if(isset($config[$key])){
//                $configuration[$key] = $config[$key];
//            }
//        }
        return $configuration;
    }
}
