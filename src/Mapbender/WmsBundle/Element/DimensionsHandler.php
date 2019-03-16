<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\BoundConfigMutator;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\Component\DimensionInst;

/**
 * Dimensions handler
 * @author Paul Schmidt
 */
class DimensionsHandler extends Element implements BoundConfigMutator
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
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                '@MapbenderWmsBundle/Resources/public/mapbender.element.dimensionshandler.js',
            ),
            'css' => array(
                '@MapbenderWmsBundle/Resources/public/sass/element/dimensionshandler.scss',
                '@MapbenderCoreBundle/Resources/public/sass/element/mbslider.scss',
            ),
            'trans' => array(
                'MapbenderWmsBundle:Element:dimensionshandler.json.twig',
            ),
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

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {

        if (in_array($this->entity->getRegion(), array('toolbar', 'footer'))) {
            return "MapbenderWmsBundle:Element:dimensionshandler.toolbar{$suffix}";
        } else {
            return "MapbenderWmsBundle:Element:dimensionshandler{$suffix}";
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        foreach ($configuration['dimensionsets'] as $setKey => $setConfig) {
            if (!empty($setConfig['dimension']) && is_object($setConfig['dimension'])) {
                /** @var DimensionInst $dimension */
                $dimension = $setConfig['dimension'];
                $dimensionConfig = $dimension->getConfiguration();
                $configuration['dimensionsets'][$setKey]['dimension'] = $dimensionConfig;
            }
        }
        return $configuration;
    }

    /**
     * Replace dimension entries in generated frontend config with our desired values.
     *
     * @param mixed[] $appConfig
     * @return mixed[]
     */
    public function updateAppConfig($appConfig)
    {
        $configuration = parent::getConfiguration();
        $instances = array();
        foreach ($configuration['dimensionsets'] as $key => $value) {
            foreach (ArrayUtil::getDefault($value, 'group', array()) as $group) {
                $item = explode("-", $group);
                $instances[$item[0]] = $value['dimension'];
            }
        }
        if (!$instances) {
            // nothing to do, skip looping over all the layer configs
            return $appConfig;
        }

        foreach ($appConfig['layersets'] as &$layerList) {
            foreach ($layerList as &$layerMap) {
                foreach ($layerMap as $layerId => &$layerDef) {
                    if (empty($instances[$layerId]) || empty($layerDef['configuration']['options']['dimensions'])) {
                        // layer is not controllable through DimHandler, leave its config alone
                        continue;
                    }
                    $dimConfig = $instances[$layerId]->getConfiguration();
                    $this->updateDimensionConfig($layerDef['configuration']['options']['dimensions'], $dimConfig);
                }
            }
        }
        return $appConfig;
    }

    /**
     * Updates the $target list of dimension config arrays by reference with our own settings (from backend).
     * Matching is by type. If a dimension config entry matches on type, we copy our "extent" and "default" into it.
     *
     * @param mixed[] $target
     * @param mixed[] $dimensionConfig
     */
    public static function updateDimensionConfig(&$target, $dimensionConfig)
    {
        foreach ($target as &$dimensionDef) {
            if ($dimensionDef['type'] == $dimensionConfig['type']) {
                $dimensionDef['extent'] = $dimensionConfig['extent'];
                $dimensionDef['default'] = $dimensionConfig['default'];
            }
        }
    }
}
