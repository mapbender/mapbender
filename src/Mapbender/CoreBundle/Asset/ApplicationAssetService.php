<?php


namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\StringAsset;
use Mapbender\Component\Application\TemplateAssetDependencyInterface;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Mapbender\FrameworkBundle\Component\ElementFilter;

/**
 * Produces merged application assets.
 * Registered in container at mapbender.application_asset.service
 * @see ApplicationAssetOverrides for overriding specific resources
 */
class ApplicationAssetService
{
    protected bool $debugOpenlayers = false;

    public function __construct(protected CssCompiler                 $cssCompiler,
                                protected JsCompiler                  $jsCompiler,
                                protected TranslationCompiler         $translationCompiler,
                                protected ElementFilter               $elementFilter,
                                protected ElementInventoryService     $inventory,
                                protected TypeDirectoryService        $sourceTypeDirectory,
                                protected ApplicationTemplateRegistry $templateRegistry,
                                protected ApplicationAssetOverrides   $overrides,
                                protected bool                        $debug = false,
                                protected bool                        $strict = false,
    )
    {
    }

    /**
     * @return string[]
     */
    public function getValidAssetTypes(bool $sourceMap = false): array
    {
        if ($sourceMap) return ['js', 'css'];
        return ['js', 'css', 'trans'];
    }

    /**
     * Get asset content for a specific application in the frontend
     */
    public function getAssetContent(Application $application, string $type, bool $sourceMap, ?string $sourceMapRoute): string
    {
        if (!in_array($type, $this->getValidAssetTypes($sourceMap), true)) {
            throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
        $references = $this->collectAssetReferences($application, $type);
        $assetType = $sourceMap ? 'map.' . $type : $type;
        return $this->compileAssetContent($references, $assetType, $sourceMapRoute);
    }

    /**
     * Get asset content for backend or login.
     */
    public function getBackendAssetContent(TemplateAssetDependencyInterface $source, string $type, bool $sourceMap, ?string $sourceMapRoute): string
    {
        if (!in_array($type, $this->getValidAssetTypes(), true)) {
            throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
        $referenceLists = array(
            $this->getBaseAssetReferences($type),
            $source->getAssets($type),
            $source->getLateAssets($type),
        );
        $references = array_unique(call_user_func_array('\array_merge', $referenceLists));
        $assetType = $sourceMap ? 'map.' . $type : $type;
        return $this->compileAssetContent($references, $assetType, $sourceMapRoute);
    }

    /**
     * Retrieves all required assets from elements, templates, etc. that are needed to display an application
     * @return string[]
     */
    protected function collectAssetReferences(Application $application, $type): array
    {
        $referenceLists = array();
        if ($type === 'css') {
            $template = $this->templateRegistry->getApplicationTemplate($application);
            $variables = $template->getSassVariablesAssets($application);

            $customVariables = $this->extractSassVariables($application->getCustomCss() ?: '');
            if ($customVariables) {
                $variables[] = new StringAsset($customVariables . "\n");
            }
            $referenceLists[] = $variables;
        }
        $referenceLists = array_merge($referenceLists, array(
            $this->getBaseAssetReferences($type),
            $this->getFrontendBaseAssets($type),
            $this->getLayerAssetReferences($application, $type),
            $this->getTemplateBaseAssetReferences($application, $type),
            $this->getElementAssetReferences($application, $type),
            $this->getTemplateLateAssetReferences($application, $type),
        ));
        $references = call_user_func_array('\array_merge', $referenceLists);
        // Append `extra_assets` references (only occurs in YAML application, see ApplicationYAMLMapper)
        $extraYamlAssetGroups = $application->getExtraAssets() ?: array();
        $extraYamlRefs = ArrayUtil::getDefault($extraYamlAssetGroups, $type, array());
        $references = array_merge($references, $extraYamlRefs);

        $references = $this->deduplicate($references);
        $references = $this->replaceWithOverrides($references);

        if ($type === 'css') {
            $customCss = trim($application->getCustomCss());
            if ($customCss) {
                $references[] = new StringAsset($customCss);
            }
        }
        return $references;
    }

    protected function compileAssetContent(array $refs, string $type, ?string $sourceMapRoute): string
    {
        return match ($type) {
            'css' => $this->cssCompiler->compile($refs),
            'map.css' => $this->debug ? $this->cssCompiler->createMap($refs) : "",
            'js' => $this->jsCompiler->compile($refs, $this->debug, $sourceMapRoute),
            'map.js' => $this->debug ? $this->jsCompiler->createMap($refs) : "",
            'trans' => $this->translationCompiler->compile($refs),
            default => throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true)),
        };
    }

    /**
     * provides assets required by frontend and backend
     * @return string[]
     */
    protected function getBaseAssetReferences(string $type): array
    {
        return match ($type) {
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/polyfills.js',
                '@MapbenderCoreBundle/Resources/public/stubs.js',
                '@MapbenderCoreBundle/Resources/public/util.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                '/bundles/mapbendercore/regional/vendor/notify.0.3.2.min.js',
                '@MapbenderCoreBundle/Resources/public/widgets/dropdown.js',
            ),
            'trans' => array(
                'mb.actions.*',
                'mb.terms.*',
            ),
            default => array(),
        };
    }

    /**
     * provides assets required by the frontend (for all applications)
     * @return string[]
     */
    protected function getFrontendBaseAssets(string $type): array
    {
        switch ($type) {
            case 'js':
                if ($this->debug && $this->debugOpenlayers) {
                    $openlayers = '../vendor/mapbender/openlayers6-es5/dist/ol-debug.js';
                    $proj4js = '/components/proj4js/dist/proj4-src.js';
                } else {
                    $openlayers = '../vendor/mapbender/openlayers6-es5/dist/ol.js';
                    $proj4js = '/components/proj4js/dist/proj4.js';
                }

                return [
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapModelBase.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.application.js',
                    '@MapbenderCoreBundle/Resources/public/mb-action.js',
                    '@MapbenderCoreBundle/Resources/public/init/element-sidepane.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/toolbar-menu.js',
                    '/components/datatables/core/js/dataTables.min.js',
                    '/components/datatables/bootstrap5/js/dataTables.bootstrap5.min.js',
                    '@MapbenderCoreBundle/Resources/public/init/frontend.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/mapbender.popup.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/tabcontainer.js',
                    $openlayers,
                    '@MapbenderCoreBundle/Resources/public/ol6-ol4-compat.js',
                    $proj4js,
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/LayerGroup.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/LayerSet.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/SourceLayer.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/Source.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/GetFeatureInfoSource.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/sourcetree-util.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/StyleUtil.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/FileUtil.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.element.map.mapaxisorder.js',
                    '@MapbenderCoreBundle/Resources/public/init/projection.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngine.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngineOl4.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/NotMapQueryMap.js',
                    "@MapbenderCoreBundle/Resources/public/mapbender.model.js",
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerPool.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerBridge.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerPoolOl4.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerBridgeOl4.js',
                    '@MapbenderCoreBundle/Resources/public/elements/MapbenderElement.js',
                ];
            case 'css':
                return [
                    "@MapbenderCoreBundle/Resources/public/sass/modules/mapPopup.scss",
                    '/components/datatables/bootstrap5/css/dataTables.bootstrap5.css',
                ];
        }
        return [];
    }

    /**
     * Provides assets required by the application template
     * @return string[]
     */
    protected function getTemplateBaseAssetReferences(Application $application, string $type): array
    {
        $templateComponent = $this->templateRegistry->getApplicationTemplate($application);
        return $templateComponent->getAssets($type);
    }

    /**
     * Provides assets required by the elements used in an application
     * @return string[]
     */
    protected function getElementAssetReferences(Application $application, string $type): array
    {
        $combinedRefs = array();
        // Skip grants checks here to avoid issues with application asset caching.
        // Non-granted Elements will skip HTML rendering and config and will not be initialized.
        // Emitting the base js / css / translation assets OTOH is always safe to do
        foreach ($this->elementFilter->filterFrontend($application->getElements(), false, false) as $element) {
            $elementRefs = $this->getSingleElementAssetReferences($element, $type);
            $combinedRefs = array_merge($combinedRefs, $elementRefs);
        }
        return $combinedRefs;
    }

    /**
     * provides assets required by an element
     * @return string[]
     */
    protected function getSingleElementAssetReferences(Element $element, $type): array
    {
        if ($handler = $this->inventory->getHandlerService($element)) {
            $fullElementRefs = $handler->getRequiredAssets($element);
        } else {
            try {
                // Migrate to potentially update class
                $this->elementFilter->migrateConfig($element);
                $handlingClass = $element->getClass();
            } catch (ElementErrorException) {
                // for frontend presentation, incomplete / invalid elements are silently suppressed
                // => return nothing
                return array();
            }
            assert(\is_a($handlingClass, 'Mapbender\CoreBundle\Component\Element', true));
            /** @todo: update ElementFilter to clarify shim usage */
            $shimService = $this->inventory->getFrontendHandler($element);
            $fullElementRefs = $shimService->getRequiredAssets($element);
        }
        return ArrayUtil::getDefault($fullElementRefs ?: array(), $type, array());
    }

    /**
     * Provides assets needed for a specific application (e.g. for WMSSource)
     * @return string[]
     */
    protected function getLayerAssetReferences(Application $application, string $type): array
    {
        return $this->sourceTypeDirectory->getScriptAssets($application, $type);
    }

    /**
     * @return string[]
     */
    protected function getTemplateLateAssetReferences(Application $application, string $type): array
    {
        $templateComponent = $this->templateRegistry->getApplicationTemplate($application);
        return $templateComponent->getLateAssets($type);
    }

    protected function extractSassVariables(string $customCss): string
    {
        // Strip multiline comments (including unclosed at the end)
        $customCss = \preg_replace('#/\*.*?(\*/|$)#Ds', '', $customCss);
        // Strip single-line comments
        $customCss = \preg_replace('#//[^\n]*$#', '', $customCss);
        // Strip leading whitespace
        $customCss = ltrim($customCss);
        $variableLines = array();
        foreach (\explode("\n", $customCss) as $line) {
            if (\trim($line) && \preg_match('#^\s*\$.*?:#', $line)) {
                $variableLines[] = $line;
            }
        }
        return \implode("\n", $variableLines);
    }

    /**
     * Replace references where an override was registered.
     * @see ApplicationAssetOverrides::registerAssetOverride()
     */
    protected function replaceWithOverrides(array $references): array
    {
        for ($i = 0; $i < count($references); $i++) {
            $ref = $references[$i];
            if (!is_string($ref)) continue;
            if (array_key_exists($ref, $this->overrides->getMap())) {
                $references[$i] = $this->overrides->getMap()[$ref];
            }
        }
        return $references;
    }

    /**
     * ~array_unique but compatible with StringAsset (and maybe other objects)
     * Non-string inputs are retained without inspection.
     */
    private function deduplicate(array $references): array
    {
        $seen = array();
        $refsOut = array();
        foreach ($references as $reference) {
            if (!\is_string($reference)) {
                $refsOut[] = $reference;
            } elseif (empty($seen[$reference])) {
                $seen[$reference] = true;
                $refsOut[] = $reference;
            }
        }
        return $refsOut;
    }
}
