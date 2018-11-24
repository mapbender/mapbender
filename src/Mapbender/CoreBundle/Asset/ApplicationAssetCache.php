<?php

namespace Mapbender\CoreBundle\Asset;

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

    /**
     * ApplicationAssetCache constructor.
     *
     * @param ContainerInterface $container
     * @param (string|StringAsset)[]      $inputs
     */
    public function __construct(ContainerInterface $container, $inputs)
    {
        parent::__construct($container, null);
        $this->inputs = $inputs;
    }

    /**
     * @return \Assetic\Asset\AssetCollection
     */
    public function fill()
    {
        return $this->buildAssetCollection($this->inputs);
    }
}
