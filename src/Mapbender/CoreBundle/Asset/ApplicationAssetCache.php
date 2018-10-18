<?php

namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\AssetCollection;
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
class ApplicationAssetCache extends AssetFactoryBase
{
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
        parent::__construct($container);
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

        $assets = new AssetCollection(array(), array(), $assetRootPath);
        $assets->setTargetPath($this->targetPath);

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
            $fileAsset = $this->makeFileAsset($input, $this->type);

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
            if('scss' === pathinfo($fileAsset->getSourcePath(), PATHINFO_EXTENSION)) $needsCompiling = true;

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
    protected function getTargetPath()
    {
        $route = $this->container->get('router')->getRouteCollection()->get('mapbender_core_application_assets');
        $target = str_replace('\\', '/', realpath($this->container->get('kernel')->getRootDir() . '/../web/app.php')) . $route->getPath();
        return $target;
    }

    /**
     * @param string $fileName
     * @param string $assetType one of 'js', 'css', 'trans'
     * @return object[]
     * @todo: figure out assetic filter base class
     */
    protected function getFilters($fileName, $assetType)
    {
        $filters = array();
        if ('css' === $assetType) {
            if ('scss' === pathinfo($fileName, PATHINFO_EXTENSION)) {
                $filters[] = $this->container->get('assetic.filter.compass');
            }
            $filters[] = $this->container->get('assetic.filter.cssrewrite');
        }
        return $filters;
    }
}
