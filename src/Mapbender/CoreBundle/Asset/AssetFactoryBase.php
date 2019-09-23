<?php


namespace Mapbender\CoreBundle\Asset;

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
     * @param string[] $bundleClassMap
     */
    public function __construct(FileLocatorInterface $fileLocator, $webDir, $bundleClassMap)
    {
        $this->fileLocator = $fileLocator;
        $this->webDir = $webDir;
        $this->publishedBundleNameMap = $this->initPublishedBundlePaths($bundleClassMap);
    }

    /**
     * Calculates a mapping from published web-relative path containing a bundle's public assets to the bundle
     * name. Input is a mapping of canonical bundle name to bundle FQCN, as provided by Symfony's standard
     * kernel.bundles parameter.
     *
     * @param string[] $bundleClassMap
     * @return string[]
     */
    protected function initPublishedBundlePaths($bundleClassMap)
    {
        $nameMap = array();
        foreach (array_keys($bundleClassMap) as $bundleName) {
            $publishedPath = 'bundles/' . strtolower(preg_replace('#Bundle$#', '', $bundleName));
            $nameMap[$publishedPath] = $bundleName;
        }
        return $nameMap;
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
