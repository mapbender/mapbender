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

    protected $migratedRefs = array();

    /**
     * @param FileLocatorInterface $fileLocator
     * @param string $webDir
     */
    public function __construct(FileLocatorInterface $fileLocator, $webDir)
    {
        $this->fileLocator = $fileLocator;
        $this->webDir = $webDir;
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
                $realAssetPath = $this->locateAssetFile($input);
                $fileAsset = new FileAsset($realAssetPath);
                $fileAsset->setTargetPath($targetPath);
                $uniqueKey = str_replace(array('@', 'Resources/public/'), '', $input);
                $uniqueKey = str_replace(array('/', '.', '-'), '__', $uniqueKey);
                if ($debug) {
                    $debugInfo = $this->getDebugHeader($realAssetPath, $input);
                    $uniqueAssets["{$uniqueKey}+dbgInfo"] = new StringAsset($debugInfo);
                }
                $uniqueAssets[$uniqueKey] = $fileAsset;
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
        return $this->fileLocator->locate($this->getSourcePath($input));
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
