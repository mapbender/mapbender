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
use Mapbender\Utils\AssetReferenceUtil;

/**
 * Produces merged application assets.
 * Registered in container at mapbender.application_asset.service
 */
class ApplicationAssetService
{
    /** @var CssCompiler */
    protected $cssCompiler;
    /** @var JsCompiler */
    protected $jsCompiler;
    /** @var TranslationCompiler */
    protected $translationCompiler;
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var ElementInventoryService */
    protected $inventory;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;
    /** @var ApplicationTemplateRegistry */
    protected $templateRegistry;
    /** @var bool */
    protected $debug;
    /** @var bool */
    protected $strict;

    public function __construct(CssCompiler $cssCompiler,
                                JsCompiler $jsCompiler,
                                TranslationCompiler $translationCompiler,
                                ElementFilter $elementFilter,
                                ElementInventoryService $inventory,
                                TypeDirectoryService $sourceTypeDirectory,
                                ApplicationTemplateRegistry $templateRegistry,
                                $debug=false,
                                $strict=false)
    {
        $this->cssCompiler = $cssCompiler;
        $this->jsCompiler = $jsCompiler;
        $this->translationCompiler = $translationCompiler;
        $this->elementFilter = $elementFilter;
        $this->inventory = $inventory;
        $this->sourceTypeDirectory = $sourceTypeDirectory;
        $this->templateRegistry = $templateRegistry;
        $this->debug = $debug;
        $this->strict = $strict;
    }

    /**
     * @return string[]
     */
    public function getValidAssetTypes(bool $sourceMap = false)
    {
        if ($sourceMap) return ['js', 'css'];
        return ['js', 'css', 'trans'];
    }

    /**
     * @param Application $application
     * @param string $type
     * @return string
     */
    public function getAssetContent(Application $application, string $type, bool $sourceMap, ?string $sourceMapRoute)
    {
        if (!in_array($type, $this->getValidAssetTypes($sourceMap), true)) {
            throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
        $refs = $this->collectAssetReferences($application, $type);
        $assetType = $sourceMap ? 'map.' . $type : $type;
        return $this->compileAssetContent(null, $refs, $assetType, $sourceMapRoute);
    }

    /**
     * Get asset content for backend or login.
     *
     * @param TemplateAssetDependencyInterface $source
     * @param string $type
     * @return string
     */
    public function getBackendAssetContent(TemplateAssetDependencyInterface $source, string $type, bool $sourceMap, ?string $sourceMapRoute)
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
        return $this->compileAssetContent(null, $references, $assetType, $sourceMapRoute);
    }

    /**
     * @param Application $application
     * @param $type
     * @return string[]
     */
    public function collectAssetReferences(Application $application, $type)
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
        $referenceLists[] = $this->getBaseAssetReferences($type);
        $referenceLists[] = $this->getFrontendBaseAssets($type);
        $referenceLists = array_merge($referenceLists, array(
            $this->getMapEngineAssetReferences($application, $type),
            $this->getTemplateBaseAssetReferences($application, $type),
            $this->getElementAssetReferences($application, $type),
            $this->getTemplateLateAssetReferences($application, $type),
        ));
        $references = call_user_func_array('\array_merge', $referenceLists);
        $references = AssetReferenceUtil::deduplicate($references);
        // Append `extra_assets` references (only occurs in YAML application, see ApplicationYAMLMapper)
        $extraYamlAssetGroups = $application->getExtraAssets() ?: array();
        $extraYamlRefs = ArrayUtil::getDefault($extraYamlAssetGroups, $type, array());
        $references = array_merge($references, $extraYamlRefs);
        switch ($type) {
            case 'css':
                $customCss = trim($application->getCustomCss());
                if ($customCss) {
                    $references[] = new StringAsset($customCss);
                }
                break;
            default:
                // do nothing
                break;
        }
        return $references;
    }

    /**
     * @param string $configSlug
     * @param string[]|StringAsset[] $refs
     * @param string $type
     * @return string
     */
    protected function compileAssetContent($configSlug, $refs, $type, ?string $sourceMapRoute)
    {
        switch ($type) {
            case 'css':
                return $this->cssCompiler->compile($refs);
            case 'map.css':
                return $this->debug ? $this->cssCompiler->createMap($refs) : "";
            case 'js':
                return $this->jsCompiler->compile($refs, $configSlug, $this->debug, $sourceMapRoute);
            case 'map.js':
                return $this->debug ? $this->jsCompiler->createMap($refs) : "";
            case 'trans':
                // JSON does not support embedded comments, so ignore $debug here
                return $this->translationCompiler->compile($refs);
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    /**
     * @param Application $application
     * @param string $type
     * @return string[]
     */
    public function getMapEngineAssetReferences(Application $application, $type)
    {
        $engineCode = $application->getMapEngineCode();
        switch ("{$engineCode}-{$type}") {
            case 'ol2-js':
                $commonAssets = array(
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/sourcetree-util.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/StyleUtil.js',
                    '@MapbenderCoreBundle/Resources/public/proj4js/proj4js-compressed.js',
                    '@MapbenderCoreBundle/Resources/public/init/projection.js',
                    '/../vendor/mapbender/mapquery/lib/openlayers/OpenLayers.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.element.map.mapaxisorder.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngine.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngineOl2.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/source.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/NotMapQueryMap.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.model.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerPool.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerBridge.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerPoolOl2.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerBridgeOl2.js',
                );
                break;
            default:
                $commonAssets = array();
                break;
            case 'ol4-js':  // legacy identifier
            case Application::MAP_ENGINE_CURRENT . '-js':
                if (!$this->debug) {
                    $openlayers = '../vendor/mapbender/openlayers6-es5/dist/ol.js';
                    $proj4js = '/components/proj4js/dist/proj4.js';
                } else {
                    $openlayers = '../vendor/mapbender/openlayers6-es5/dist/ol-debug.js';
                    $proj4js = '/components/proj4js/dist/proj4-src.js';
                }

                $commonAssets = array(
                    $openlayers,
                    '@MapbenderCoreBundle/Resources/public/ol6-ol4-compat.js',
                    $proj4js,
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/source.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/sourcetree-util.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/StyleUtil.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.element.map.mapaxisorder.js',
                    '@MapbenderCoreBundle/Resources/public/init/projection.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngine.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngineOl4.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/source.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/NotMapQueryMap.js',
                    "@MapbenderCoreBundle/Resources/public/mapbender.model.ol4.js",
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerPool.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerBridge.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerPoolOl4.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/VectorLayerBridgeOl4.js',
                );
                break;
            case 'ol4-css':
            case Application::MAP_ENGINE_CURRENT . '-css':
                return array(
                    "@MapbenderCoreBundle/Resources/public/sass/modules/mapPopup.scss",
                );
                break;
        }
        return array_merge($commonAssets, $this->getLayerAssetReferences($application, $type));
    }

    /**
     * @param string $type
     * @return string[]
     */
    protected function getBaseAssetReferences($type)
    {
        switch ($type) {
            case 'js':
                return array(
                    '@MapbenderCoreBundle/Resources/public/polyfills.js',
                    '@MapbenderCoreBundle/Resources/public/stubs.js',
                    '@MapbenderCoreBundle/Resources/public/util.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                    '/components/underscore/underscore-min.js',
                    '/bundles/mapbendercore/regional/vendor/notify.0.3.2.min.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/dropdown.js',
                );
                break;
            case 'trans':
                return array(
                    'mb.actions.*',
                    'mb.terms.*',
                );
            default:
                return array();
        }
    }

    protected function getFrontendBaseAssets($type)
    {
        switch ($type) {
            default:
                return array();
            case 'js':
                return array(
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapModelBase.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.application.js',
                    '@MapbenderCoreBundle/Resources/public/mb-action.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.element.base.js',
                    '@MapbenderCoreBundle/Resources/public/init/element-sidepane.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/toolbar-menu.js',
                    '/components/datatables/media/js/jquery.dataTables.min.js',
                    '@MapbenderCoreBundle/Resources/public/init/frontend.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/mapbender.popup.js',
                    '@MapbenderCoreBundle/Resources/public/element/mbDialogElement.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/tabcontainer.js',
                );
        }
    }

    /**
     * @param Application $application
     * @param string $type
     * @return string[]
     */
    public function getTemplateBaseAssetReferences(Application $application, $type)
    {
        $templateComponent = $this->templateRegistry->getApplicationTemplate($application);
        $refs = $templateComponent->getAssets($type);
        return $this->qualifyAssetReferencesBulk($templateComponent, $refs, $type);
    }

    /**
     * @param Application $application
     * @param string $type
     * @return string[]
     */
    public function getElementAssetReferences(Application $application, $type)
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

    protected function getSingleElementAssetReferences(Element $element, $type)
    {
        if ($handler = $this->inventory->getHandlerService($element)) {
            $fullElementRefs = $handler->getRequiredAssets($element);
        } else {
            try {
                // Migrate to potentially update class
                $this->elementFilter->migrateConfig($element);
                $handlingClass = $element->getClass();
            } catch (ElementErrorException $e) {
                // for frontend presentation, incomplete / invalid elements are silently suppressed
                // => return nothing
                return array();
            }
            assert(\is_a($handlingClass, 'Mapbender\CoreBundle\Component\Element', true));
            /** @todo: update ElementFilter to clarify shim usage */
            $shimService = $this->inventory->getFrontendHandler($element);
            $fullElementRefs = $shimService->getRequiredAssets($element);
        }
        // NOTE: no support for automatically amending asset bundle scope based on class name (=legacy)
        return ArrayUtil::getDefault($fullElementRefs ?: array(), $type, array());
    }

    /**
     * @param Application $application
     * @param string $type
     * @return string[]
     */
    protected function getLayerAssetReferences(Application $application, $type)
    {
        switch ($type) {
            case 'js':
                return $this->sourceTypeDirectory->getScriptAssets($application);
            case 'trans':
            case 'css':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported type " . print_r($type, true));
        }
    }

    /**
     * @param Application $application
     * @param string $type
     * @return string[]
     */
    public function getTemplateLateAssetReferences(Application $application, $type)
    {
        $templateComponent = $this->templateRegistry->getApplicationTemplate($application);
        $refs = $templateComponent->getLateAssets($type);
        return $this->qualifyAssetReferencesBulk($templateComponent, $refs, $type);
    }

    /**
     * Amends magically implicit bundle scope in unqualified assetic resource references.
     * 'file.js' => '@MapbenderMagicallyImplicitBundle/Resources/public/file.js'
     *
     * @param object $scopeObject
     * @param string[] $references
     * @param string $type
     * @return string[]
     * @deprecated only use asset references assetic understands without further processing
     */
    protected function qualifyAssetReferencesBulk($scopeObject, $references, $type)
    {
        // NOTE: Translations assets are views (twig templates); they never supported
        //       automatic bundle namespace inferrence, and they still don't.
        if ($type === 'trans') {
            return $references;
        } else {
            return AssetReferenceUtil::qualifyBulk($scopeObject, $references, $this->strict);
        }
    }

    /**
     * @param string $customCss
     * @return string
     */
    protected function extractSassVariables($customCss)
    {
        // Strip multiline comments (including unclosed at the end)
        $customCss = \preg_replace('#/\*.*?(\*/|$)#Ds', '', $customCss);
        // Strip single-line comments
        $customCss = \preg_replace('#//[^\n]*$#', '', $customCss);
        // Strip leading whitespace
        $customCss = \preg_replace('#^\s+#', '', $customCss);
        $variableLines = array();
        foreach (\explode("\n", $customCss) as $line) {
            if (\trim($line) && \preg_match('#^\s*\$.*?:#', $line)) {
                $variableLines[] = $line;
            }
        }
        return \implode("\n", $variableLines);
    }
}
