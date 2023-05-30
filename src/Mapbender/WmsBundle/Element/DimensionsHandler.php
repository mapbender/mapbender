<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\WmsBundle\Component\DimensionInst;

/**
 * Dimensions handler
 * @author Paul Schmidt
 */
class DimensionsHandler extends AbstractElementService implements ConfigMigrationInterface
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
            'dimensionsets' => array()
            
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbDimensionsHandler';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                '@MapbenderWmsBundle/Resources/public/mapbender.element.dimensionshandler.js',
            ),
            'css' => array(
                '@MapbenderWmsBundle/Resources/public/sass/element/dimensionshandler.scss',
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

    public function getView(Element $element)
    {
        $dimensionsets = $this->normalizeDimensionsets($element);
        if (!$dimensionsets) {
            return false;
        }

        if (preg_match('#(toolbar|footer)#', $element->getRegion())) {
            $view = new TemplateView('MapbenderWmsBundle:Element:dimensionshandler.toolbar.html.twig');
            $view->attributes['title'] = $element->getTitle() ?: $this->getClassTitle();
        } else {
            $view = new TemplateView('MapbenderWmsBundle:Element:dimensionshandler.html.twig');
        }
        $view->attributes['class'] = 'mb-element-dimensionshandler';
        $view->variables['dimensionsets'] = $dimensionsets;
        return $view;
    }

    protected function normalizeDimensionsets(Element $element)
    {
        $dimensionsets = array();
        foreach ($element->getConfiguration()['dimensionsets'] as $setConfig) {
            if (!empty($setConfig['group'])) {
                if (empty($setConfig['title'])) {
                    $setConfig['title'] = $this->generateDimensionLabel($setConfig);
                }
                $dimensionsets[] = $setConfig;
            }
        }
        return $dimensionsets;
    }

    protected function generateDimensionLabel(array $setConfig)
    {
        foreach ($setConfig['group'] as $targetDimension) {
            return \preg_replace('#^.*-(\w+)-\w*$#', '${1}', $targetDimension);
        }
        // Uh-oh!
        return '';
    }

    public static function updateEntityConfig(Element $entity)
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
