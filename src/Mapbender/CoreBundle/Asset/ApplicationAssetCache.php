<?php

namespace Mapbender\CoreBundle\Asset;

use Assetic\AssetManager;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\StringAsset;
use Assetic\Asset\FileAsset;
use Assetic\Cache\FilesystemCache;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ApplicationAssetCache
{
    protected $container;
    protected $inputs;
    protected $type;
    protected $targetPath;
    protected $force;

    public function __construct(ContainerInterface $container, $inputs, $type, $force = false)
    {
        $this->container = $container;
        $this->inputs = $inputs;
        $this->type = $type;
        $this->targetPath = $this->getTargetPath();
        $this->force = $force;
    }

    public function fill($slug = null, $useTimestamp = false)
    {
        $static_assets_cache_path = $this->container->getParameter('mapbender.static_assets_cache_path');

        // For each asset build compiled, cached asset
        $assetRootPath = $this->getAssetRootPath();
        $assetTargetPath = $this->targetPath;

        $assets = new AssetCollection(array(), array(), $assetRootPath);
        $assets->setTargetPath($this->targetPath);

        $locator = $this->container->get('file_locator');
        $manager = new AssetManager();
        $cache = new FilesystemCache($static_assets_cache_path);
        $devCache = null;
        if($this->container->get('kernel')->isDebug() && $this->container->getParameter('mapbender.sass_assets')) {
            $devCache = new FilesystemCache($static_assets_cache_path . '/.dev-cache');
        }

        foreach($this->inputs as $input) {
            if($input instanceof StringAsset) {
                $manager->set($name, $input);
                continue;
            }

            // First, build file asset with filters and public path information
            $file = $locator->locate($input);
            $publicSourcePath = $this->getPublicSourcePath($input);

            // Build filter list (None for JS/Trans, Compass for SASS and Rewrite for SASS/CSS)
            $filters = array();
            if('css' === $this->type) {
                if('scss' === pathinfo($file, PATHINFO_EXTENSION)) {
                    $filters[] = $this->container->get('assetic.filter.compass');
                }
                $filters[] = $this->container->get('assetic.filter.cssrewrite');
            }

            $fileAsset = new FileAsset($file, $filters, null, $assetRootPath . '/' . $publicSourcePath);
            $fileAsset->setTargetPath($this->targetPath);

            $name = str_replace(array('@', 'Resources/public/'), '', $input);
            $name = str_replace(array('/', '.', '-'), '__', $name);

            // If we want to compile on the fly, two caches have to be used - one for on-the-fly and one for later on
            if($devCache) {
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
        }

        // Finally, wrap everything into a single asset collection
        foreach($manager->getNames() as $name) {
            $assets->add($manager->get($name));
        }

        return $assets;
    }

    protected function getAssetRootPath()
    {
        return dirname($this->container->getParameter('kernel.root_dir')) . '/web';
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

    protected function getTargetPath()
    {
        $route = $this->container->get('router')->getRouteCollection()->get('mapbender_core_application_assets');
        $target = realpath($this->container->get('kernel')->getRootDir() . '/../web/app.php') . $route->getPattern();
        return $target;
    }
}
