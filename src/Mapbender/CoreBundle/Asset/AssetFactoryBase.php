<?php


namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\StringAsset;
use Symfony\Component\Config\FileLocatorInterface;

class AssetFactoryBase
{
    /** @var string */
    protected $webDir;
    /** @var FileLocatorInterface */
    protected $fileLocator;

    /**
     * @param FileLocatorInterface $fileLocator
     * @param string $webDir
     */
    public function __construct(FileLocatorInterface $fileLocator, $webDir)
    {
        $this->fileLocator = $fileLocator;
        $this->webDir = $webDir;
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param string|null $targetPath
     * @return AssetCollection
     */
    protected function buildAssetCollection($inputs, $targetPath)
    {
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

        $collection = new AssetCollection($uniqueAssets, array(), $this->webDir);
        $collection->setTargetPath($targetPath);

        return $collection;
    }

    /**
     * @param string $input reference to an asset file
     * @return FileAsset
     */
    protected function makeFileAsset($input)
    {
        $sourcePath = $this->fileLocator->locate($this->getSourcePath($input));
        $fileAsset = new FileAsset($sourcePath);

        return $fileAsset;
    }

    /**
     * @param $input
     * @return string
     */
    protected function getSourcePath($input)
    {
        $matches = array();
        if (preg_match('#^@([\w]+)Bundle/Resources/public/(.+)$#i', $input, $matches)) {
            // rewrite reference to look into published bundle asset dir below web
            $input = implode('/', array(
                '/bundles',
                strtolower($matches[1]), // lower-case bundle name minus 'bundle'
                $matches[2],             // remainder of reference after 'public/'
            ));
        }
        if ($input[0] == '/') {
            $inWeb = $this->webDir . '/' . ltrim($input, '/');
            if (@is_file($inWeb) && @is_readable($inWeb)) {
                return $inWeb;
            }
        }
        // This makes FileLocator look in app/Resources, but in a way that's not tied to bundle structure
        // and doesn't support asset drop-in replacements.
        return $input;
    }
}
