<?php


namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\StringAsset;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Presenter\ApplicationService;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\ManagerBundle\Template\ManagerTemplate;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Produces merged application assets.
 * Registered in container at mapbender.application_asset.service
 */
class ApplicationAssetService
{
    /** @var ApplicationService */
    protected $applicationService;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;
    /** @var ElementFactory */
    protected $elementFactory;
    /** @var RouterInterface */
    protected $router;
    /** @var ContainerInterface */
    protected $dummyContainer;
    /** @var AssetFactory */
    protected $compiler;
    /** @var EngineInterface */
    protected $templateEngine;
    /** @var bool */
    protected $debug;
    /** @var bool */
    protected $strict;

    public function __construct(AssetFactory $compiler,
                                ApplicationService $applicationService,
                                TypeDirectoryService $sourceTypeDirectory,
                                ElementFactory $elementFactory,
                                RouterInterface $router,
                                EngineInterface $templateEngine,
                                $debug=false,
                                $strict=false)
    {
        $this->compiler = $compiler;
        $this->applicationService = $applicationService;
        $this->sourceTypeDirectory = $sourceTypeDirectory;
        $this->elementFactory = $elementFactory;
        $this->router = $router;
        $this->templateEngine = $templateEngine;
        $this->debug = $debug;
        $this->strict = $strict;
        $this->dummyContainer = new Container();
        $this->dummyContainer->set('templating', new \stdClass());
    }

    /**
     * @return string[]
     */
    public function getValidAssetTypes()
    {
        return array(
            'js',
            'css',
            'trans',
        );
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string
     */
    public function getAssetContent(Entity\Application $application, $type)
    {
        if (!in_array($type, $this->getValidAssetTypes(), true)) {
            throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
        $refs = $this->collectAssetReferences($application, $type);
        return $this->compileAssetContent($application, $refs, $type);
    }

    /**
     * @param Entity\Application $application
     * @param $type
     * @return string[]
     */
    public function collectAssetReferences(Entity\Application $application, $type)
    {
        $referenceLists = array(
            $this->getBaseAssetReferences($application, $type),
            $this->getTemplateBaseAssetReferences($application, $type),
            $this->getElementAssetReferences($application, $type),
            $this->getTemplateLateAssetReferences($application, $type),
        );
        $references = call_user_func_array('\array_merge', $referenceLists);
        $references = array_unique($references);
        // Append `extra_assets` references (only occurs in YAML application, see ApplicationYAMLMapper)
        $extraYamlAssetGroups = $application->getExtraAssets() ?: array();
        $extraYamlRefs = ArrayUtil::getDefault($extraYamlAssetGroups, $type, array());
        $references = array_merge($references, $extraYamlRefs);
        switch ($type) {
            case 'js':
                $references[] = new StringAsset($this->renderAppLoader($application));
                break;
            case 'css':
                $references[] = new StringAsset(trim($application->getCustomCss()));
                break;
            default:
                // do nothing
                break;
        }
        return $references;
    }

    /**
     * @param Entity\Application $application
     * @param string[] $refs
     * @param string $type
     * @return string
     */
    protected function compileAssetContent(Entity\Application $application, $refs, $type)
    {
        switch ($type) {
            case 'css':
                $sourcePath = $this->getCssAssetSourcePath();
                $targetPath = $this->getCssAssetTargetPath($application);
                return $this->compiler->compileCss($refs, $sourcePath, $targetPath, $this->debug);
            case 'js':
                return $this->compiler->compileRaw($refs, $this->debug);
            case 'trans':
                // JSON does not support embedded comments, so ignore $debug here
                return $this->compiler->compileTranslations($refs);
            default:
                throw new \InvalidArgumentException("Unsupported asset type " . print_r($type, true));
        }
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string[]
     */
    public function getMapEngineAssetReferences(Entity\Application $application, $type)
    {
        switch ($type) {
            case 'js':
                $commonAssets = array(
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/sourcetree-util.js',
                    '@MapbenderCoreBundle/Resources/public/proj4js/proj4js-compressed.js',
                    '@MapbenderCoreBundle/Resources/public/init/projection.js',
                    '/../vendor/mapbender/mapquery/lib/openlayers/OpenLayers.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.element.map.mapaxisorder.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngine.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/MapEngineOl2.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/source.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.model.js',
                    '/../vendor/mapbender/mapquery/lib/jquery/jquery.tmpl.js',
                );
                break;
            default:
                $commonAssets = array();
                break;
        }
        return array_merge($commonAssets, $this->getLayerAssetReferences($application, $type));
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string[]
     */
    public function getBaseAssetReferences(Entity\Application $application, $type)
    {
        switch ($type) {
            case 'js':
                $commonAssets = array(
                    '@MapbenderCoreBundle/Resources/public/stubs.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.application.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.application.wdt.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.element.base.js',
                    '@MapbenderCoreBundle/Resources/public/init/element-sidepane.js',
                    '@MapbenderCoreBundle/Resources/public/polyfills.js',
                    '/components/underscore/underscore-min.js',
                    '/bundles/mapbendercore/regional/vendor/notify.0.3.2.min.js',
                    '/components/datatables/media/js/jquery.dataTables.min.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/mapbender.popup.js',
                    '@MapbenderCoreBundle/Resources/public/widgets/mapbender.checkbox.js',
                    '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                );
                break;
            default:
                $commonAssets = array();
                break;
        }
        return array_merge($commonAssets, $this->getMapEngineAssetReferences($application, $type));
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string[]
     */
    public function getTemplateBaseAssetReferences(Entity\Application $application, $type)
    {
        $templateComponent = $this->getDummyTemplateComponent($application);
        $refs = $templateComponent->getAssets($type);
        return $this->qualifyAssetReferencesBulk($templateComponent, $refs, $type);
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string[]
     */
    public function getElementAssetReferences(Entity\Application $application, $type)
    {
        $combinedRefs = array();
        // Skip grants checks here to avoid issues with application asset caching.
        // Non-granted Elements will skip HTML rendering and config and will not be initialized.
        // Emitting the base js / css / translation assets OTOH is always safe to do
        foreach ($this->applicationService->getActiveElements($application, false) as $element) {
            $elementRefs = ArrayUtil::getDefault($element->getAssets() ?: array(), $type, array());
            $qualifiedRefs = $this->qualifyAssetReferencesBulk($element, $elementRefs, $type);
            $combinedRefs = array_merge($combinedRefs, $qualifiedRefs);
        }
        return $combinedRefs;
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string[]
     */
    protected function getLayerAssetReferences(Entity\Application $application, $type)
    {
        switch ($type) {
            case 'js':
            case 'trans':
                return $this->sourceTypeDirectory->getAssets($application, $type);
            case 'css':
                return array();
            default:
                throw new \InvalidArgumentException("Unsupported type " . print_r($type, true));
        }
    }

    /**
     * @param Entity\Application $application
     * @param string $type
     * @return string[]
     */
    public function getTemplateLateAssetReferences(Entity\Application $application, $type)
    {
        $templateComponent = $this->getDummyTemplateComponent($application);
        $refs = $templateComponent->getLateAssets($type);
        return $this->qualifyAssetReferencesBulk($templateComponent, $refs, $type);
    }

    /**
     * @param Entity\Application $application
     * @return string
     */
    protected function getCssAssetTargetPath(Entity\Application $application)
    {
        return $this->router->generate('mapbender_core_application_assets', array(
            'slug' => $application->getSlug(),
            'type' => 'css',
        ));
    }

    /**
     * Returns JavaScript code with final client-side application initialization.
     * This should be the very last bit, following all other JavaScript definitions
     * and initializations.
     * @param Entity\Application $application
     * @return string
     */
    protected function renderAppLoader(Entity\Application $application)
    {
        $appLoaderTemplate = '@MapbenderCoreBundle/Resources/views/application.config.loader.js.twig';
        return $this->templateEngine->render($appLoaderTemplate, array(
            'application' => $application,
        ));
    }

    /**
     * @param Entity\Application $application
     * @return Template|ManagerTemplate
     */
    protected function getDummyTemplateComponent(Entity\Application $application)
    {
        $templateClassName = $application->getTemplate();
        $appComp = $this->elementFactory->appComponentFromEntity($application);
        /** @var Template|ManagerTemplate $instance */
        $instance = new $templateClassName($this->dummyContainer, $appComp);
        return $instance;
    }

    /**
     * @return string
     */
    protected function getCssAssetSourcePath()
    {
        // Calculate the subfolder under the current host that contains the web directory
        // (actually, the entry script) from the Router's RequestContext.
        // This is equivalent to calling Request::getBasePath
        $baseUrl = $this->router->getContext()->getBaseUrl();
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        $beforeScript = implode('', array_slice(explode($scriptName, $baseUrl), 0, 1));
        return rtrim($beforeScript, '/') ?: '.';
    }

    /**
     * Amend given bundle-implicit assetic $reference with bundle scope from
     * given $scopeObject. If the $reference is already bundle-qualified, return
     * it unmodified.
     * If the passed reference is interpreted as a web-anchored file path (starts with '/')
     * or an app/Resources-relative path (starts with '.'), also return it unmodified.
     *
     * @param object $scopeObject
     * @param string $reference
     * @return string
     */
    protected function qualifyAssetReference($scopeObject, $reference)
    {
        // If it starts with an @ we assume it's already an assetic reference
        $firstChar = $reference[0];
        if ($firstChar === '@' || $firstChar === '/' || $firstChar === '.') {
            return $reference;
        } else {
            if (!$scopeObject) {
                throw new \RuntimeException("Can't resolve asset path $reference with empty object context");
            }
            $message = "Missing explicit bundle path in asset reference "
                     . print_r($reference, true)
                     . " from " . get_class($scopeObject);
            if ($this->strict) {
                throw new \RuntimeException($message);
            } else {
                @trigger_error("Deprecated: {$message}", E_USER_DEPRECATED);
            }
            $namespaces = explode('\\', get_class($scopeObject));
            $bundle     = sprintf('%s%s', $namespaces[0], $namespaces[1]);
            return sprintf('@%s/Resources/public/%s', $bundle, $reference);
        }
    }

    /**
     * Bulk version of qualifyAssetReference
     *
     * @param object $scopeObject
     * @param string[] $references
     * @param string $type
     * @return string[]
     */
    protected function qualifyAssetReferencesBulk($scopeObject, $references, $type)
    {
        // NOTE: Translations assets are views (twig templates); they never supported
        //       automatic bundle namespace inferrence, and they still don't.
        if ($type !== 'trans') {
            $refsOut = array();
            foreach ($references as $singleRef) {
                $refsOut[] = $this->qualifyAssetReference($scopeObject, $singleRef);
            }
            return $refsOut;
        } else {
            return $references;
        }
    }
}
