<?php

namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\AssetCache;
use Assetic\Asset\AssetInterface;
use Assetic\Cache\CacheInterface;
use Assetic\Filter\FilterInterface;


class NamedAssetCache extends AssetCache
{
    private $name;
    private $asset;
    private $cache;
    private $suffix;
    private $useTimestamp;

    public function __construct($name, AssetInterface $asset, CacheInterface $cache, $suffix = null, $useTimestamp = false)
    {
        $this->name = $name;
        $this->asset = $asset;
        $this->cache = $cache;
        $this->suffix = $suffix;
        $this->useTimestamp = $useTimestamp;

        parent::__construct($asset, $cache);
    }

    public function load(FilterInterface $additionalFilter = null)
    {
        $cacheKey = self::getCacheKey($this->name, $this->asset, $additionalFilter, '', $this->suffix, $this->useTimestamp);
        if ($this->cache->has($cacheKey)) {
            $this->asset->setContent($this->cache->get($cacheKey));

            return;
        }

        $this->asset->load($additionalFilter);
        $this->cache->set($cacheKey, $this->asset->getContent());
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        $cacheKey = self::getCacheKey($this->name, $this->asset, $additionalFilter, '', $this->suffix, $this->useTimestamp);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $content = $this->asset->dump($additionalFilter);
        $this->cache->set($cacheKey, $content);

        return $content;
    }

    private static function getCacheKey($name, AssetInterface $asset, FilterInterface $additionalFilter = null, $salt = '', $suffix, $useTimestamp= false)
    {
        if ($additionalFilter) {
            $asset = clone $asset;
            $asset->ensureFilter($additionalFilter);
        }

        $cacheKey = $name;
        if($useTimestamp) {
            $cacheKey .= $asset->getLastModified();
        }

        foreach ($asset->getFilters() as $filter) {
            if ($filter instanceof HashableInterface) {
                $cacheKey .= $filter->hash();
            } else {
                $cacheKey .= serialize($filter);
            }
        }

        if ($values = $asset->getValues()) {
            asort($values);
            $cacheKey .= serialize($values);
        }

        $key = $name . '-' . substr(sha1($cacheKey . $salt), 0, 7);
        $key .= $suffix ? $suffix : '';
        return $key;
    }
}
