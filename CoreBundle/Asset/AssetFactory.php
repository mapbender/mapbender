<?php

namespace Mapbender\CoreBundle\Asset;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Assetic\Asset\AssetCollection;
use Assetic\AssetManager;
use Assetic\Asset\StringAsset;
use Assetic\Asset\FileAsset;


class AssetFactory
{
    protected $container;
    protected $inputs;
    protected $type;
    protected $targetPath;
    protected $collection;
    protected $sourcePath;

    public function __construct(ContainerInterface $container, array $inputs, $type, $targetPath, $sourcePath)
    {
        $this->sourcePath = $sourcePath;
        $this->container = $container;
        $this->inputs = $inputs;
        $this->type = $type;
        $this->targetPath = $targetPath;
    }

    public function getAssetCollection()
    {
        if(!$this->collection) {
            $assetRootPath = dirname($this->container->getParameter('kernel.root_dir')) . '/web';
            $this->collection = new AssetCollection(array(), array(), $assetRootPath);
            $this->collection->setTargetPath($this->targetPath);
            $locator = $this->container->get('file_locator');
            $manager = new AssetManager();
            $stringAssetCounter = 0;

            foreach($this->inputs as $input) {
                // GUI declared CSS
                if($input instanceof StringAsset) {
                    $name = 'stringasset_' . $stringAssetCounter++;
                    $manager->set($name, $input);
                    continue;
                }

                // First, build file asset with public path information
                $file = $locator->locate($input);
                $publicSourcePath = $assetRootPath . '/' . $this->getPublicSourcePath($input);

                $fileAsset = new FileAsset(
                    $file,
                    array(),
                    $assetRootPath,
                    $publicSourcePath);
                $fileAsset->setTargetPath($this->targetPath);

                $name = str_replace(array('@', 'Resources/public/'), '', $input);
                $name = str_replace(array('/', '.', '-'), '__', $name);
                $manager->set($name, $fileAsset);
            }

            // Finally, wrap everything into a single asset collection
            foreach($manager->getNames() as $name) {
                $this->collection->add($manager->get($name));
            }
        }

        return $this->collection;
    }

    public function compile()
    {
        $filters = array();
        $isDebug = $this->container->get('kernel')->isDebug();
        $content = $this->getAssetCollection()->dump();

        if('css' === $this->type) {
            $sass = clone $this->container->get('mapbender.assetic.filter.sass');
            $sass->setStyle($isDebug ? 'nested' : 'compressed');
            $filters[] = $sass;
            $filters[] = $this->container->get("assetic.filter.cssrewrite");
            $content = $this->squashImports($content);
        }
        // Web source path
        $assets = new StringAsset($content, $filters, '/', $this->sourcePath);
        $assets->setTargetPath($this->targetPath);
        return $assets->dump();
    }

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

    protected function getPublicSourcePath($input)
    {
        $sourcePath = null;
        if ($input[0] == '@') {
            // Bundle name
            $bundle = substr($input, 1, strpos($input, '/') - 1);
            // Path inside the Resources/public folder
            $assetPath = substr($input,
                strlen('@' . $bundle . '/Resources/public'));

            return 'bundles/' . preg_replace('/bundle$/', '', strtolower($bundle)) . $assetPath;
        }
    }
}
