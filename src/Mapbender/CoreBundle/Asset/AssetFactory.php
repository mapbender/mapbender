<?php
namespace Mapbender\CoreBundle\Asset;

/**
 * Compiles and merges JavaScript, (S)CSS and translation assets.
 * Registered in container at mapbender.asset_compiler.service
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 * @package Mapbender\CoreBundle\Asset
 */
class AssetFactory
{
    /** @var CssCompiler */
    protected $cssCompiler;
    /** @var JsCompiler */
    protected $jsCompiler;
    /** @var TranslationCompiler */
    protected $translationCompiler;

    /**
     * @param CssCompiler $cssCompiler
     * @param JsCompiler $jsCompiler
     * @param TranslationCompiler $translationCompiler
     */
    public function __construct(CssCompiler $cssCompiler,
                                JsCompiler $jsCompiler,
                                TranslationCompiler $translationCompiler)
    {
        $this->cssCompiler = $cssCompiler;
        $this->jsCompiler = $jsCompiler;
        $this->translationCompiler = $translationCompiler;
    }

    /**
     * Perform simple concatenation of all input assets. Some uniquification will take place.
     *
     * @param (FileAsset|StringAsset)[] $inputs
     * @param bool $debug to enable file input markers
     * @return string
     * @todo: absorb service ownership into ApplicationAssetService
     */
    public function compileRaw($inputs, $debug)
    {
        return $this->jsCompiler->compile($inputs, $debug);
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param string $sourcePath for adjusting relative urls in css rewrite filter
     * @param string $targetPath
     * @param bool $debug to enable file input markers
     * @return string
     * @todo: absorb service ownership into ApplicationAssetService
     */
    public function compileCss($inputs, $sourcePath, $targetPath, $debug=false)
    {
        return $this->cssCompiler->compile($inputs, $sourcePath, $targetPath, $debug);
    }

    /**
     * @param string[] $inputs names of json.twig files
     * @return string JavaScript initialization code
     * @todo: absorb service ownership into ApplicationAssetService
     */
    public function compileTranslations($inputs)
    {
        return $this->translationCompiler->compile($inputs);
    }
}
