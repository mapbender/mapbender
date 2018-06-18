<?php


namespace Mapbender\CoreBundle\Asset;

use Assetic\Asset\FileAsset;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AssetFactoryBase
{
    /** @var ContainerInterface  */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $fileName
     * @param string $assetType one of 'js', 'css', 'trans'
     * @return object[]
     * @todo: figure out assetic filter base class
     */
    protected function getFilters($fileName, $assetType)
    {
        return array();
    }

    /**
     * @param string $input reference to an asset file
     * @param string $type
     * @return FileAsset
     */
    protected function makeFileAsset($input, $type)
    {
        /** @var FileLocator $locator */
        $locator = $this->container->get('file_locator');

        $sourcePath = $this->getSourcePath($input);
        if ($sourcePath) {
            $file = $locator->locate($sourcePath);
        } else {
            $file = $locator->locate($input);
        }
        // Build filter list (None for JS/Trans, Compass for SASS and Rewrite for SASS/CSS)
        $filters = $this->getFilters($file, $type);
        $fileAsset = new FileAsset($file, $filters, null, null);

        return $fileAsset;
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
    protected function getWebDir()
    {
        return dirname($this->container->getParameter('kernel.root_dir')) . '/web';
    }
}
