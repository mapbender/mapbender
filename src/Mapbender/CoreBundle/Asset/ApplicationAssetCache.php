<?php

namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\StringAsset;
use Assetic\AssetManager;
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
        $assetRootPath = $this->getWebDir();

        $assets = new AssetCollection(array(), array(), $assetRootPath);
        $assets->setTargetPath($this->targetPath);

        $manager = new AssetManager();

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

            $manager->set($name, $fileAsset);
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
