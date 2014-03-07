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

        $filters = array(
            'js'    => array(),
            'trans' => array(),
            'css'   => array(
                $this->container->get('assetic.filter.compass'),
                $this->container->get('assetic.filter.cssrewrite')));

        // For each asset build compiled, cached asset
        $assetRootPath = $this->getAssetRootPath();
        $assetTargetPath = $this->targetPath;

        $assets = new AssetCollection(array(), $filters[$this->type], $assetRootPath);
        $assets->setTargetPath($this->targetPath);

        $locator = $this->container->get('file_locator');
        $manager = new AssetManager();
        $cache = new FilesystemCache($static_assets_cache_path);
        foreach($this->inputs as $input) {
            if($input instanceof StringAsset) {
                continue;
            }

            // First, build file asset with filters and public path information
            $file = $locator->locate($input);
            $publicSourcePath = $this->getPublicSourcePath($input);
            $fileAsset = new FileAsset($file, $filters[$this->type], null, $assetRootPath . '/' . $publicSourcePath);
            $fileAsset->setTargetPath($this->targetPath);

            // Then wrap it into a cached asset, which required as valid cache name first
            $name = str_replace(array('@', 'Resources/public/'), '', $input);
            $name = str_replace(array('/', '.', '-'), '__', $name);
            $cachedAsset = new NamedAssetCache($name, $fileAsset, $cache, '.' . $this->type, $useTimestamp, $this->force);

            // Dump the cached asset for one-time compilation
            $cachedAsset->dump();

            // Collect all assets into a manager for dupe removal
            $manager->set($name, $cachedAsset);
        }

        // Finally, wrap everything into a single asset collection
        foreach($manager->getNames() as $name) {
            $assets->add($manager->get($name));
        }

        // If we we're building for a specified application, cache the final result, too
        if(null !== $slug) {
            // @todo
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
