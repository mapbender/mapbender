<?php
namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\FileAsset;
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
    /** @var array|\Assetic\Asset\FileAsset[]|\Assetic\Asset\StringAsset[]  */
    protected $inputs;

    /** @var string string */
    protected $sourcePath;

    /**
     * AssetFactory constructor.
     *
     * @param ContainerInterface              $container
     * @param StringAsset[]|FileAsset[]|array $inputs
     * @param string                          $targetPath
     * @param string                          $sourcePath
     */
    public function __construct(ContainerInterface $container, array $inputs, $targetPath, $sourcePath)
    {
        parent::__construct($container, $targetPath);
        $this->sourcePath = $sourcePath;
        $this->container  = $container;
        $this->inputs     = $inputs;
    }

    /**
     *
     * @return string
     */
    public function compile()
    {
        $filters   = array();
        $container = $this->container;
        $isDebug   = $container->get('kernel')->isDebug();
        $sass = clone $container->get('mapbender.assetic.filter.sass');
        $sass->setStyle($isDebug ? 'nested' : 'compressed');
        $content = $this->buildAssetCollection($this->inputs)->dump();

        $sass = clone $container->get('mapbender.assetic.filter.sass');
        $sass->setStyle($isDebug ? 'nested' : 'compressed');
        $filters[] = $sass;
        $filters[] = $container->get("assetic.filter.cssrewrite");
        $content = $this->squashImports($content);

        // Web source path
        $assets = new StringAsset($content, $filters, '/', $this->sourcePath);
        $assets->setTargetPath($this->targetPath);
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
