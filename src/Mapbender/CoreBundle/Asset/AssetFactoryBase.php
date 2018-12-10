<?php


namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\StringAsset;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AssetFactoryBase
{
    /** @var ContainerInterface  */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param string|null $targetPath
     * @return AssetCollection
     */
    protected function buildAssetCollection($inputs, $targetPath)
    {
        $assetRootPath = $this->getWebDir();
        $uniqueAssets = array();
        $stringAssetCounter = 0;

        foreach ($inputs as $input) {
            if ($input instanceof StringAsset) {
                $uniqueKey = 'stringasset_' . $stringAssetCounter++;
                $uniqueAssets[$uniqueKey] = $input;
            } else {
                $fileAsset = $this->makeFileAsset($input);
                $fileAsset->setTargetPath($targetPath);
                $uniqueKey = str_replace(array('@', 'Resources/public/'), '', $input);
                $uniqueKey = str_replace(array('/', '.', '-'), '__', $uniqueKey);
                $uniqueAssets[$uniqueKey] = $fileAsset;
            }
        }

        $collection = new AssetCollection($uniqueAssets, array(), $assetRootPath);
        $collection->setTargetPath($targetPath);

        return $collection;
    }

    /**
     * @param string $input reference to an asset file
     * @return FileAsset
     */
    protected function makeFileAsset($input)
    {
        /** @var FileLocator $locator */
        $locator = $this->container->get('file_locator');

        $sourcePath = $this->getSourcePath($input);
        if ($sourcePath) {
            $file = $locator->locate($sourcePath);
        } else {
            $file = $locator->locate($input);
        }
        $fileAsset = new FileAsset($file);

        return $fileAsset;
    }

    /**
     * @param $input
     * @return string
     */
    protected function getSourcePath($input)
    {
        if ($input[0] == '@') {
            // Bundle name
            $bundle = substr($input, 1, strpos($input, '/') - 1);
            // Path inside the Resources/public folder
            $assetPath = substr($input,
                strlen('@' . $bundle . '/Resources/public'));
            $assetDir = 'bundles/' . preg_replace('/bundle$/', '', strtolower($bundle));

            return $this->getSourcePath($assetDir . $assetPath);
        } else {
            $webRoot = $this->getWebDir();
            $inWeb = $webRoot . '/' . ltrim($input, '/');
            if (@is_file($inWeb) && @is_readable($inWeb)) {
                return $inWeb;
            }
        }
    }

    /**
     * @return string
     */
    protected function getWebDir()
    {
        return dirname($this->container->getParameter('kernel.root_dir')) . '/web';
    }
}
