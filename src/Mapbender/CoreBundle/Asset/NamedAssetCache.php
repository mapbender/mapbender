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
    private $force;

    public function __construct($name, AssetInterface $asset, CacheInterface $cache, $suffix = null, $useTimestamp = false, $force = false)
    {
        $this->name = $name;
        $this->asset = $asset;
        $this->cache = $cache;
        $this->suffix = $suffix;
        $this->useTimestamp = $useTimestamp;
        $this->force = $force;

        parent::__construct($asset, $cache);
    }

    public function isCached(FilterInterface $additionalFilter = NULL)
    {
        $cacheKey = $this->getKey($additionalFilter);
        return !$this->force && $this->cache->has($cacheKey);
    }

    public function getKey(FilterInterface $additionalFilter = NULL)
    {
        return self::getCacheKey2($this->name, $this->asset, $additionalFilter, '', $this->suffix, $this->useTimestamp);
    }

    public function load(FilterInterface $additionalFilter = NULL)
    {
        $cacheKey = $this->getKey($additionalFilter);
        if ($this->isCached($additionalFilter)) {
            $this->asset->setContent($this->cache->get($cacheKey));
            return;
        }

        $this->asset->load($additionalFilter);
        $this->cache->set($cacheKey, $this->asset->getContent());
    }

    public function dump(FilterInterface $additionalFilter = NULL)
    {
        $cacheKey = $this->getKey($additionalFilter);
        if($this->isCached()) {
            return $this->cache->get($cacheKey);
        }

        $content = $this->asset->dump($additionalFilter);
        $this->cache->set($cacheKey, $content);

        return $content;
    }

    /**
     * Stupid naming for stupid PHP 5.3 which has stupid overloading
     */
    private static function getCacheKey2($name, AssetInterface $asset, FilterInterface $additionalFilter = NULL, $salt = '', $suffix, $useTimestamp= false)
    {
        if(!$useTimestamp) {
            // Return early for no hash at all
            return ($suffix ? $name . $suffix : $name);
        }

        if ($additionalFilter) {
            $asset = clone $asset;
            $asset->ensureFilter($additionalFilter);
        }

        $cacheKey = $name;
        if($useTimestamp) {
            $cacheKey .= $asset->getLastModified();
        }

        // This has to be disabled as stupid compass does magic and gets configured differently when
        // running in CLI yielding a different cache key...
        /*
        foreach ($asset->getFilters() as $filter) {
            if ($filter instanceof HashableInterface) {
                $cacheKey .= $filter->hash();
            } else {
                $cacheKey .= serialize($filter);
            }
        }
        */

        if ($values = $asset->getValues()) {
            asort($values);
            $cacheKey .= serialize($values);
        }

        $key = $name . '-' . sha1($cacheKey . $salt);
        $key .= $suffix ? $suffix : '';
        return $key;
    }
}
