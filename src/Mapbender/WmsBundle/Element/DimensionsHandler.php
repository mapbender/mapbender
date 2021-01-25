<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;
use Mapbender\WmsBundle\Component\DimensionInst;

/**
 * Dimensions handler
 * @author Paul Schmidt
 */
class DimensionsHandler extends Element implements ConfigMigrationInterface
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
            $templateDimensionName = null;
            foreach ($setConfig['group'] as $targetDimension) {
                $templateDimensionName = \preg_replace('#^.*-(\w+)-\w*$#', '${1}', $targetDimension);
                break;
            }
            $configuration['dimensionsets'][$setKey]['dimension'] = array(
                // dimension.name is used in template only
                // @todo: update / santitize template variable access expectations
                'name' => $templateDimensionName,
            );
        }
        return $configuration;
    }

    public static function updateEntityConfig(Entity\Element $entity)
    {
        $config = $entity->getConfiguration();
        $dimensionsets = array();
        if (!empty($config['dimensionsets'])) {
            foreach ($config['dimensionsets'] as $key => $setConfig) {
                // Convert legacy serialized DimensionInst objects 'dimension' to scalar string 'extent'
                if (empty($setConfig['group']) || (empty($setConfig['dimension']) && empty($setConfig['extent']))) {
                    // Entry non-salvagable => drop
                    continue;
                }
                if (!empty($setConfig['dimension']) && \is_a($setConfig['dimension'], 'Mapbender\WmsBundle\Component\DimensionInst', true)) {
                    $extent = $setConfig['dimension']->getExtent();
                    if (is_array($extent)) {
                        // Reconstruct single-string type extent / attempt to undo getData transformation
                        // Fortunately, DimensionsHandler has historically only ever supported intervals
                        /** @see DimensionInst::getData */
                        $extent = implode('/', $extent);
                    }
                    $setConfig['extent'] = $extent;
                }
                unset($setConfig['dimension']);
                $dimensionsets[$key] = $setConfig;
            }
        }
        $config['dimensionsets'] = $dimensionsets;
        $entity->setConfiguration($config);
    }
}
