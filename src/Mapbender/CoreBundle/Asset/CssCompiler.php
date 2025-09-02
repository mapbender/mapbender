<?php


namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\StringAsset;
use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\ScssphpFilter;
use Mapbender\CoreBundle\Validator\Constraints\Scss;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Routing\RouterInterface;
use ScssPhp\ScssPhp\OutputStyle;

/**
 * Locates, merges and compiles (S)CSS assets for applications.
 *
 * Default implementation for service mapbender.asset_compiler.css
 * @since v3.0.8.5-beta1
 */
class CssCompiler extends AssetFactoryBase
{
    protected FilterInterface $cssRewriteFilter;

    public function __construct(FileLocatorInterface $fileLocator,
                                LoggerInterface $logger,
                                $webDir, $bundleClassMap,
                                /** @var ScssphpFilter  */
                                protected FilterInterface $sassFilter,
                                protected RouterInterface $router)
    {
        parent::__construct($fileLocator, $logger, $webDir, $bundleClassMap);
        $this->cssRewriteFilter = new CssRewriteFilter();
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param bool $debug to enable file input markers
     * @return string
     */
    public function compile($inputs, $debug=false)
    {
        $content = $this->concatenateContents($inputs, $debug);

        $this->sassFilter->setOutputStyle($debug ? OutputStyle::EXPANDED : OutputStyle::COMPRESSED);
        $filters = array(
            $this->sassFilter,
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
