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
    /** @var string[] */
    protected $publishedBundleNameMap;

    protected $migratedRefs = array();

    /**
     * @param FileLocatorInterface $fileLocator
     * @param string $webDir
     * @param string[] $publishedBundleNameMap
     */
    public function __construct(FileLocatorInterface $fileLocator, $webDir, $publishedBundleNameMap)
    {
        $this->fileLocator = $fileLocator;
        $this->webDir = $webDir;
        $this->publishedBundleNameMap = $publishedBundleNameMap;
    }

    protected function getDebugHeader($finalPath, $originalRef)
    {
        return "\n"
            . "/** \n"
            . "  * BEGIN NEW ASSET INPUT -- {$finalPath}\n"
            . "  * (original reference: {$originalRef})\n"
            . "  */\n"
        ;
    }

    /**
     * @param (StringAsset|string)[] $inputs
     * @param string|null $targetPath
     * @param bool $debug to emit file markers
     * @return AssetCollection
     */
    protected function buildAssetCollection($inputs, $targetPath, $debug=false)
    {
        $uniqueAssets = array();
        $stringAssetCounter = 0;

        foreach ($inputs as $input) {
            if ($input instanceof StringAsset) {
                $uniqueKey = 'stringasset_' . $stringAssetCounter++;
                $uniqueAssets[$uniqueKey] = $input;
            } else {
                $normalizedReference = $this->normalizeReference($input);
                $realAssetPath = $this->locateAssetFile($normalizedReference);
                $fileAsset = new FileAsset($realAssetPath);
                $fileAsset->setTargetPath($targetPath);
                if ($debug) {
                    $debugInfo = $this->getDebugHeader($realAssetPath, $input);
                    $uniqueAssets["{$normalizedReference}+dbgInfo"] = new StringAsset($debugInfo);
                }
                $uniqueAssets[$normalizedReference] = $fileAsset;
            }
        }
        $collection = new AssetCollection($uniqueAssets, array(), $this->webDir);
        $collection->setTargetPath($targetPath);

        return $collection;
    }

    /**
     * @param string $input reference to an asset file
     * @return string resolved absolute path to file
     */
    protected function locateAssetFile($input)
    {
        while (!empty($this->migratedRefs[$input])) {
            $input = $this->migratedRefs[$input];
        }
        if ($input[0] == '/') {
            $inWeb = $this->webDir . '/' . ltrim($input, '/');
            if (@is_file($inWeb) && @is_readable($inWeb)) {
                return realpath($inWeb);
            }
        }
        return $this->fileLocator->locate($input);
    }

    /**
     * Retranslates published asset reference ("/bundles/somename/apath/something.ext") back to bundle-scoped
     * reference ("@SomeNameBundle/Resources/public/apath/something.ext"), which allows FileLocator to pick
     * up resource overrides in app/Resources.
     *
     * @param string $input
     * @return string
     */
    protected function normalizeReference($input)
    {
        if ($input && preg_match('#^/bundles/.+/.+#', $input)) {
            $parts = explode('/', $input, 4);
            $publishedBundleName = $parts[2];
            if (!empty($this->publishedBundleNameMap[$publishedBundleName])) {
                $pathInside = $parts[3];
                return '@' . $this->publishedBundleNameMap[$publishedBundleName] . '/Resources/public/' . $pathInside;
            }
        }
        return $input;
    }
}
