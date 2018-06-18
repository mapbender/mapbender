<?php

namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;
use Assetic\AssetManager;
use Assetic\Cache\FilesystemCache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApplicationAssetCache
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 * @package Mapbender\CoreBundle\Asset
 */
class ApplicationAssetCache
{
    /** @var ContainerInterface  */
    protected $container;

    /** @var array|\Assetic\Asset\FileAsset[]|\Assetic\Asset\StringAsset[]  */
    protected $inputs;

    /** @var string  */
    protected $type;

    /** @var string  */
    protected $targetPath;

    protected $force;

    /**
     * ApplicationAssetCache constructor.
     *
     * @param ContainerInterface $container
     * @param (string|StringAsset)[]      $inputs
     * @param                    $type
     * @param bool               $force
     */
    public function __construct(ContainerInterface $container, $inputs, $type, $force = false)
    {
        $this->container = $container;
        $this->inputs = $inputs;
        $this->type = $type;
        $this->targetPath = $this->getTargetPath();
        $this->force = $force;
    }

    /**
     * @param string|null $slug Application name
     * @param bool|false $useTimestamp
     * @return AssetCollection
     */
    public function fill($slug = null, $useTimestamp = false)
    {
        $static_assets_cache_path = $this->container->getParameter('mapbender.static_assets_cache_path');

        // For each asset build compiled, cached asset
        $assetRootPath = $this->getWebDir();
        $assetTargetPath = $this->targetPath;

        $assets = new AssetCollection(array(), array(), $assetRootPath);
        $assets->setTargetPath($this->targetPath);

        $locator = $this->container->get('file_locator');
        $manager = new AssetManager();
        $cache = new FilesystemCache($static_assets_cache_path);
        $devCache = new FilesystemCache($static_assets_cache_path . '/.dev-cache');

        $stringAssetCounter = 0;

        foreach($this->inputs as $input) {
            if($input instanceof StringAsset) {
                $name = 'stringasset_' . $stringAssetCounter++;
                $manager->set($name, $input);
                continue;
            }

            // First, build file asset with filters and public path information
            $sourcePath = $this->getSourcePath($input);
            if ($sourcePath) {
                $file = $locator->locate($sourcePath);
            } else {
                $file = $locator->locate($input);
            }

            // Build filter list (None for JS/Trans, Compass for SASS and Rewrite for SASS/CSS)
            $filters = array();
            if('css' === $this->type) {
                if('scss' === pathinfo($file, PATHINFO_EXTENSION)) {
                    $filters[] = $this->container->get('assetic.filter.compass');
                }
                $filters[] = $this->container->get('assetic.filter.cssrewrite');
            }

            $fileAsset = new FileAsset($file, $filters, null, $assetRootPath . '/' . $sourcePath);
            $fileAsset->setTargetPath($this->targetPath);

            $name = str_replace(array('@', 'Resources/public/'), '', $input);
            $name = str_replace(array('/', '.', '-'), '__', $name);

            // Only assets which need to be compiled need to be cached. Twice that is. Once while in dev mode, taking
            // the timestamp into consideration and once for when compiling is not possible/required (regular dev or
            // prod mode). This second cache will also get updated whenever a assets needs to get updated in the dev
            // cache.
            //
            // All other assets get passed trough.
            $isDevPlus = $this->container->get('kernel')->isDebug() && $this->container->getParameter('mapbender.sass_assets');
            $needsCompiling = false;

            // SASS things need to be compiled.
            if('scss' === pathinfo($file, PATHINFO_EXTENSION)) $needsCompiling = true;

            if($needsCompiling) {
                if($isDevPlus) {
                    $devCachedAsset = new NamedAssetCache($name, $fileAsset, $devCache, '.' . $this->type, true, $this->force);
                    if(!$devCachedAsset->isCached()) {
                        $cachedAsset = new NamedAssetCache($name, $fileAsset, $cache, '.' . $this->type, false, true);
                        $cachedAsset->dump();
                    }
                    $devCachedAsset->dump();
                    $manager->set($name, $devCachedAsset);
                } else {
                    $cachedAsset = new NamedAssetCache($name, $fileAsset, $cache, '.' . $this->type, false, $this->force);
                    $cachedAsset->dump();
                    $manager->set($name, $cachedAsset);
                }
            } else {
                $manager->set($name, $fileAsset);
            }
        }

        // Finally, wrap everything into a single asset collection
        foreach($manager->getNames() as $name) {
            $assets->add($manager->get($name));
        }

        return $assets;
    }

    /**
     * @return string
     */
    protected function getWebDir()
    {
        return dirname($this->container->getParameter('kernel.root_dir')) . '/web';
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
    protected function getTargetPath()
    {
        $route = $this->container->get('router')->getRouteCollection()->get('mapbender_core_application_assets');
        $target = str_replace('\\', '/', realpath($this->container->get('kernel')->getRootDir() . '/../web/app.php')) . $route->getPath();
        return $target;
    }
}
