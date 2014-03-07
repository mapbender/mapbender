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

    public function load(FilterInterface $additionalFilter = null)
    {
        $cacheKey = self::getCacheKey2($this->name, $this->asset, $additionalFilter, '', $this->suffix, $this->useTimestamp);
        if ($this->cache->has($cacheKey)) {
            $this->asset->setContent($this->cache->get($cacheKey));

            return;
        }

        $this->asset->load($additionalFilter);
        $this->cache->set($cacheKey, $this->asset->getContent());
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        $cacheKey = self::getCacheKey2($this->name, $this->asset, $additionalFilter, '', $this->suffix, $this->useTimestamp);
        if (!$this->force && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $content = $this->asset->dump($additionalFilter);
        $this->cache->set($cacheKey, $content);

        return $content;
    }

    /**
     * Stupid naming for stupid PHP 5.3 which has stupid overloading
     */
    private static function getCacheKey2($name, AssetInterface $asset, FilterInterface $additionalFilter = null, $salt = '', $suffix, $useTimestamp= false)
    {
        // Return early for no hash at all
        return ($suffix ? $name . $suffix : $name);

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

        $key = $name . '-' . substr(sha1($cacheKey . $salt), 0, 7);
        $key .= $suffix ? $suffix : '';
        return $key;
    }
}
