<?php


namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\StringAsset;
use Mapbender\CoreBundle\Component\ElementFactory;
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
    protected $minifyCss;

    public function __construct(AssetFactory $compiler,
                                ElementFactory $elementFactory,
                                RouterInterface $router,
                                EngineInterface $templateEngine,
                                $minifyCss=false)
    {
        $this->compiler = $compiler;
        $this->elementFactory = $elementFactory;
        $this->router = $router;
        $this->templateEngine = $templateEngine;
        $this->minifyCss = $minifyCss;
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
    protected function collectAssetReferences(Entity\Application $application, $type)
    {
        $references = $this->getBaseAssetReferences($application, $type);
        $appComp = $this->elementFactory->appComponentFromEntity($application);
        $references = array_merge($references, $appComp->getAssetGroup($type));
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
                return $this->compiler->compileCss($refs, $sourcePath, $targetPath, $this->minifyCss);
            case 'js':
                return $this->compiler->compileRaw($refs);
            case 'trans':
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
    public function getBaseAssetReferences(Entity\Application $application, $type)
    {
        switch ($type) {
            case 'js':
                return array(
                    '@MapbenderCoreBundle/Resources/public/stubs.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.application.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender-model/sourcetree-util.js',
                    '@MapbenderCoreBundle/Resources/public/init/projection.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.model.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.trans.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.application.wdt.js',
                    '@MapbenderCoreBundle/Resources/public/mapbender.element.base.js',
                    '@MapbenderCoreBundle/Resources/public/polyfills.js',
                );
            default:
                return array();
        }
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
        // Calculate the subfolder under the current host that containes the web directory
        // from the Router's RequestContext.
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
     * If the passed reference is a web-anchored file path, turn it into a relative
     * file path.
     *
     * @param object $scopeObject
     * @param string $reference
     * @return string
     */
    protected function qualifyAssetReference($scopeObject, $reference)
    {
        // If it starts with an @ we assume it's already an assetic reference
        $firstChar = $reference[0];
        if ($firstChar == "/") {
            return "../../web/" . substr($reference, 1);
        } elseif ($firstChar == ".") {
            return $reference;
        } elseif ($firstChar !== '@') {
            if (!$scopeObject) {
                throw new \RuntimeException("Can't resolve asset path $reference with empty object context");
            }
            $namespaces = explode('\\', get_class($scopeObject));
            $bundle     = sprintf('%s%s', $namespaces[0], $namespaces[1]);
            return sprintf('@%s/Resources/public/%s', $bundle, $reference);
        } else {
            return $reference;
        }
    }
}
