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
     * @param FilterInterface $cssRewriteFilter
     * @param RouterInterface $router
     */
    public function __construct(FileLocatorInterface $fileLocator, $webDir, $bundleClassMap,
                                FilterInterface $sassFilter, FilterInterface $cssRewriteFilter,
                                RouterInterface $router)
    {
        parent::__construct($fileLocator, $webDir, $bundleClassMap);
        $this->sassFilter = $sassFilter;
        $this->cssRewriteFilter = $cssRewriteFilter;
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
        // Quite damaging hack for special snowflake mobile template
        // Pulls imports in front of everything else, including variable (re)definitions
        // This makes it impossible to @import modules accessing variable definitions after
        // redefining those variables.
        // @todo: make mobile template scss work with and without squashing, then disable squashing
        //        alternatively nuke mobile template completely (Mapbender 3.1?)
        $content = $this->squashImports($content);

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
     * @param $content
     * @return string
     */
    protected function squashImports($content)
    {
        preg_match_all('/\@import\s*\".*?;/s', $content, $imports, PREG_SET_ORDER);
        $imports = array_map(function($item) {
            return $item[0];
        }, $imports);
        $imports = array_unique($imports);
        $content = preg_replace('/\@import\s*\".*?;/s', '', $content);

        return implode($imports, "\n") . "\n" . $content;
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
}
