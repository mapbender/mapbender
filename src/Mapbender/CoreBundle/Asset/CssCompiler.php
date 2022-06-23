<?php


namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\StringAsset;
use Assetic\Filter\FilterInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Locates, merges and compiles (S)CSS assets for applications.
 *
 * Default implementation for service mapbender.asset_compiler.css
 * @since v3.0.8.5-beta1
 */
class CssCompiler extends AssetFactoryBase
{
    /** @var FilterInterface */
    protected $sassFilter;
    /** @var FilterInterface */
    protected $cssRewriteFilter;
    /** @var RouterInterface */
    protected $router;

    /**
     * @param FileLocatorInterface $fileLocator
     * @param string $webDir
     * @param string[] $bundleClassMap
     * @param FilterInterface $sassFilter
     * @param RouterInterface $router
     */
    public function __construct(FileLocatorInterface $fileLocator, $webDir, $bundleClassMap,
                                FilterInterface $sassFilter,
                                RouterInterface $router)
    {
        parent::__construct($fileLocator, $webDir, $bundleClassMap);
        $this->sassFilter = $sassFilter;
        $this->cssRewriteFilter = new \Assetic\Filter\CssRewriteFilter();
        $this->router = $router;
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param bool $debug to enable file input markers
     * @return string
     */
    public function compile($inputs, $debug=false)
    {
        $content = $this->concatenateContents($inputs, $debug);

        $sass = clone $this->sassFilter;
        $sass->setStyle($debug ? 'nested' : 'compressed');
        $filters = array(
            $sass,
            $this->cssRewriteFilter,
        );

        $assets = new StringAsset($content, $filters, '/', $this->getSourcePath());
        $assets->setTargetPath($this->getTargetPath());
        return $assets->dump();
    }

    /**
     * @return string
     */
    protected function getSourcePath()
    {
        // Calculate the subfolder under the current host that contains the web directory
        // (actually, the entry script) from the Router's RequestContext.
        // This is equivalent to calling Request::getBasePath
        $baseUrl = $this->router->getContext()->getBaseUrl();
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        $beforeScript = implode('', array_slice(explode($scriptName, $baseUrl), 0, 1));
        $withScript = rtrim($beforeScript, '/') . '/' . $scriptName;
        if ($baseUrl && $scriptName && 0 !== strpos($baseUrl, $withScript)) {
            // Context base url explicitly includes the name of the executed entry script.
            // => Use as is.
            return $withScript;
        } else {
            // Context base url DOES NOT include the name of the executed entry script.
            // => return stripped base url, but also provide safe minimal fallback path '.'
            return rtrim($beforeScript, '/') ?: '.';
        }
    }

    /**
     * @return string
     */
    protected function getTargetPath()
    {
        $context = $this->router->getContext();
        return $context->getBaseUrl() . $context->getPathInfo();
    }

    protected function getMigratedReferencesMapping()
    {
        return array(
            // updates for reliance on robloach/component-installer
            // select2 CSS works fine standalone (no url references) and can be sourced directly from vendor
            '/components/select2/select2-built.css' => '/../vendor/select2/select2/dist/css/select2.css',
            // Bootstrap colorpicker (from abandoned debugteam fork) absorbed into Mapbender, pre-provided in template
            '/components/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css' => array(),
        );
    }
}
