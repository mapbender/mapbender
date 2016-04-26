<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\EntityHandler;

/**
 * Dimensions handler
 * @author Paul Schmidt
 */
class DimensionsHandler extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.wms.dimhandler.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.wms.dimhandler.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
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
    public static function listAssets()
    {
        return array(
            'js' => array(
                'mapbender.wms.dimension.js',
                'mapbender.element.dimensionshandler.js',
            ),
            'css' => array(
                '@MapbenderWmsBundle/Resources/public/sass/element/dimensionshandler.scss',
                '@MapbenderCoreBundle/Resources/public/sass/element/mbslider.scss'
            ),
            'trans' => array('MapbenderWmsBundle:Element:dimensionshandler.json.twig')
        );
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
        return $this->container->get('templating')->render(
            'MapbenderWmsBundle:Element:dimensionshandler.html.twig',
            array(
                'id' => $this->getId(),
                "title" => $this->getTitle(),
                'configuration' => $this->getConfiguration()
            )
        );
    }
    
    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        foreach ($configuration['dimensionsets'] as $key => &$value) {
            $value['dimension'] = $value['dimension']->getConfiguration();
        }
        return $configuration;
    }
    
    /**
     * @inheritdoc
     */
    public function postSave()
    {
        $configuration = parent::getConfiguration();
        $instances = array();
        foreach ($configuration['dimensionsets'] as $key => $value) {
            for ($i = 0; isset($value['group']) && count($value['group']) > $i; $i++) {
                $item = explode("-", $value['group'][$i]);
                $instances[$item[0]] = $value['dimension'];
            }
        }
        foreach ($this->application->getEntity()->getLayersets() as $layerset) {
            foreach ($layerset->getInstances() as $instance) {
                if (key_exists($instance->getId(), $instances)) {
                    $handler = EntityHandler::createHandler($this->container, $instance);
                    $handler->mergeDimension($instances[$instance->getId()]);
                    $handler->save();
                }
            }
        }
    }
}
