<?php
namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\StringAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AssetFactory
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 * @package Mapbender\CoreBundle\Asset
 */
class AssetFactory extends AssetFactoryBase
{
    /**
     * AssetFactory constructor.
     *
     * @param ContainerInterface              $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    /**
     * Perform simple concatenation of all input assets. Some uniquification will take place.
     *
     * @param (FileAsset|StringAsset)[] $inputs
     * @return string
     */
    public function compileRaw($inputs)
    {
        return $this->buildAssetCollection($inputs, null)->dump();
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param string $sourcePath for adjusting relative urls in css rewrite filter
     * @param string $targetPath
     * @return string
     */
    public function compileCss($inputs, $sourcePath, $targetPath)
    {
        $container = $this->container;
        $isDebug   = $container->get('kernel')->isDebug();
        $content = $this->buildAssetCollection($inputs, $targetPath)->dump();

        $sass = clone $container->get('mapbender.assetic.filter.sass');
        $sass->setStyle($isDebug ? 'nested' : 'compressed');
        $filters = array(
            $sass,
            $container->get("assetic.filter.cssrewrite"),
        );
        $content = $this->squashImports($content);

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
