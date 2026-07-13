<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\WmsBundle\Element\Type\DimensionsHandlerAdminType;
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
    public static function getClassTitle(): string
    {
        return "mb.wms.dimhandler.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.wms.dimhandler.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            "tooltip" => "",
            'dimensionsets' => []

        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbDimensionHandler';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                '@MapbenderWmsBundle/Resources/public/MbDimensionHandler.js',
            ],
            'css' => [
                '@MapbenderWmsBundle/Resources/public/sass/element/dimensionshandler.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return DimensionsHandlerAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderWms/ElementAdmin/dimensionshandler.html.twig';
    }

    public function getView(Element $element): false|TemplateView
    {
        $dimensionsets = $this->normalizeDimensionsets($element);
        if (!$dimensionsets) {
            return false;
        }

        if (preg_match('#(toolbar|footer)#', (string) $element->getRegion())) {
            $view = new TemplateView('@MapbenderWms/Element/dimensionshandler.toolbar.html.twig');
            $view->attributes['title'] = $element->getTitle() ?: static::getClassTitle();
        } else {
            $view = new TemplateView('@MapbenderWms/Element/dimensionshandler.html.twig');
        }
        $view->attributes['class'] = 'mb-element-dimensionshandler';
        $view->variables['dimensionsets'] = $dimensionsets;
        return $view;
    }

    /**
     * @return mixed[]
     */
    protected function normalizeDimensionsets(Element $element): array
    {
        $dimensionsets = [];
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

    protected function generateDimensionLabel(array $setConfig): string|array|null
    {
        foreach ($setConfig['group'] as $targetDimension) {
            return \preg_replace('#^.*-(\w+)-\w*$#', '${1}', (string) $targetDimension);
        }
        // Uh-oh!
        return '';
    }

    public static function updateEntityConfig(Element $entity): void
    {
        $config = $entity->getConfiguration();
        $dimensionsets = [];
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
