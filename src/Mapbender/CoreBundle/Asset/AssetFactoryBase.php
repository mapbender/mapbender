<?php


namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Common base class for JsCompiler and CssCompiler
 *
 * @since v3.0.7.7
 */
class AssetFactoryBase
{
    /** @var string */
    protected $webDir;
    /** @var FileLocatorInterface */
    protected $fileLocator;
    /** @var string[] */
    protected $publishedBundleNameMap;

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
     * Perform simple concatenation of all input assets. Some uniquification will take place.
     *
     * @param (FileAsset|StringAsset|string)[] $inputs
     * @param ?string $sourceMapRoute
     * @return string
     */
    protected function concatenateContents($inputs, $sourceMapRoute)
    {
        $parts = array();
        $uniqueRefs = array();
        $migratedRefMapping = $this->getMigratedReferencesMapping();

        foreach ($inputs as $input) {
            if ($input instanceof StringAsset) {
                $input->load();
                $parts[] = $input->getContent();
            } else {
                $parts[] = $this->loadFileReference($input, $migratedRefMapping, $uniqueRefs);
            }
        }
        if ($sourceMapRoute !== null) {
            $parts[] = "\n//# sourceMappingURL=" . $sourceMapRoute;
        }
        return implode("\n", $parts);
    }

    public function createMap($inputs): string
    {
        $bundler = new SourceMapBundler();
        $uniqueRefs = array();
        $migratedRefMapping = $this->getMigratedReferencesMapping();

        foreach ($inputs as $input) {
            if ($input instanceof StringAsset) {
                $input->load();
                $bundler->skip($input->getContent());
            } else {
                $files = $this->loadFileReference($input, $migratedRefMapping, $uniqueRefs, true);
                foreach($files as $file) {
                    $bundler->addScript($file);
                }
            }
        }
        return $bundler->build();
    }

    /**
     * @param string|FileAsset $input
     * @param array $migratedRefMapping
     * @param string[] $uniqueRefs
     * @param bool $namesOnly
     * @return string|array the contents of the provides files or an array of their paths in the filesystem if $namesOnly is set to true
     */
    protected function loadFileReference($input, $migratedRefMapping, &$uniqueRefs, bool $namesOnly = false)
    {
        $parts = array();
        $normalizedReferenceBeforeRemap = $this->normalizeReference($input);

        if (!empty($uniqueRefs[$normalizedReferenceBeforeRemap])) {
            $normalizedReferences = array();
        } else {
            $normalizedReferences = $this->rewriteReference($normalizedReferenceBeforeRemap, $migratedRefMapping);
        }

        foreach ($normalizedReferences as $normalizedReference) {
            if (empty($uniqueRefs[$normalizedReference])) {
                $realAssetPath = $this->locateAssetFile($normalizedReference);
                if ($realAssetPath) {
                    $parts[] = $namesOnly ? $realAssetPath : file_get_contents($realAssetPath);
                }
                $uniqueRefs[$normalizedReference] = true;
            }
        }
        $uniqueRefs[$normalizedReferenceBeforeRemap] = true;
        return $namesOnly ? $parts : implode("\n", $parts);
    }

    /**
     * @param string $normalizedReference
     * @param array $migratedRefMapping
     * @return string[]
     */
    protected function rewriteReference($normalizedReference, $migratedRefMapping)
    {
        $refsOut = array();
        if (isset($migratedRefMapping[$normalizedReference])) {
            $replacements = (array)$migratedRefMapping[$normalizedReference];
            foreach ($replacements as $replacement) {
                if ($replacement === $normalizedReference) {
                    $refsOut[] = $replacement;
                } else {
                    foreach ($this->rewriteReference($replacement, $migratedRefMapping) as $refOut) {
                        $refsOut[] = $refOut;
                    }
                }
            }
        } else {
            $refsOut[] = $normalizedReference;
        }
        return $refsOut;
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

    /**
     * @param string $input reference to an asset file
     * @return string|null resolved absolute path to file, or null if file is missing (and should be ignored)
     */
    protected function locateAssetFile($input)
    {
        if ($input[0] == '/') {
            $inWeb = $this->webDir . '/' . ltrim($input, '/');
            if (@is_file($inWeb) && @is_readable($inWeb)) {
                return realpath($inWeb);
            }
        }
        try {
            return $this->fileLocator->locate($input);
        } catch (\InvalidArgumentException $e) {
            if (preg_match('#^[/.]*?/vendor/#', $input)) {
                // Ignore /vendor/ reference (avoid depending on internal package structure)
                return null;
            } else {
                throw $e;
            }
        }
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

    /**
     * Should return a mapping of
     *   known old, no longer valid asset file reference => new, valid reference
     * @return string[]
     */
    protected function getMigratedReferencesMapping()
    {
        return array();
    }
}
