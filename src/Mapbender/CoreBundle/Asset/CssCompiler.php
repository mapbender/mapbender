<?php


namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\StringAsset;
use Assetic\Filter\FilterInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Locates, merges and compiles (S)CSS assets for applications.
 * Registered in container as mapbender.asset_compiler.css
 */
class CssCompiler extends AssetFactoryBase
{
    /** @var FilterInterface */
    protected $sassFilter;
    /** @var FilterInterface */
    protected $cssRewriteFilter;

    /**
     * @param FileLocatorInterface $fileLocator
     * @param string $webDir
     * @param string[] $bundleClassMap
     * @param FilterInterface $sassFilter
     * @param FilterInterface $cssRewriteFilter
     */
    public function __construct(FileLocatorInterface $fileLocator, $webDir, $bundleClassMap,
                                FilterInterface $sassFilter, FilterInterface $cssRewriteFilter)
    {
        parent::__construct($fileLocator, $webDir, $bundleClassMap);
        $this->sassFilter = $sassFilter;
        $this->cssRewriteFilter = $cssRewriteFilter;
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param string $sourcePath for adjusting relative urls in css rewrite filter
     * @param string $targetPath
     * @param bool $debug to enable file input markers
     * @return string
     */
    public function compile($inputs, $sourcePath, $targetPath, $debug=false)
    {
        $content = $this->concatenateContents($inputs, $debug);
        $content = $this->squashImports($content);

        $sass = clone $this->sassFilter;
        $sass->setStyle($debug ? 'nested' : 'compressed');
        $filters = array(
            $sass,
            $this->cssRewriteFilter,
        );

        $assets = new StringAsset($content, $filters, '/', $sourcePath);
        $assets->setTargetPath($targetPath);
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
}
