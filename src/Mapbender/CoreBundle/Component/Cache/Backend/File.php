<?php


namespace Mapbender\CoreBundle\Component\Cache\Backend;
use Mapbender\BaseKernel;
use Mapbender\CoreBundle\Component\Exception\CacheMiss;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cache backend using the file system.
 * This uses the default Symfony cache directories also used by twig, doctrine etc as the base storage location.
 *
 * Cache backend provides raw key:string value storage and handles automatic invalidation based on "marks"
 * (which can be timestamps, but can also be more complex, as long as they are serializable).
 *
 * Instance registered in container at mapbender.presenter.application.cache.backend (see serviccs.xml)
 */
class File
{
    /** @var string */
    protected $rootPath;

    public function __construct(ContainerInterface $container)
    {
        /** @var BaseKernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = realpath($kernel->getCacheDir());
        if (!is_dir($rootPath) || !is_writable($rootPath)) {
            throw new \RuntimeException("Cache path {$rootPath} is not writable");
        }
        $this->rootPath = $rootPath;
    }

    /**
     * @param string[] $keyPath a multi-component key, in this backend used as a subdirectory hierarchy
     * @param string $value will be stored exactly as given
     * @param mixed $mark to determine reusability of the cache entry later in get
     */
    public function put($keyPath, $value, $mark)
    {
        // prepend root path
        array_unshift($keyPath, $this->rootPath);
        $fullPath = implode('/', $keyPath);
        @mkdir(dirname($fullPath));
        // Bake the $mark completely into the stored value. This is done to
        // 1) achieve complete atomicity of put / get
        // 2) be independent of various mtime resolutions on various filesystems when using time stamps
        // 3) support arbitrary (any serializable) marks instead of just timestamps
        $markSerialized = serialize($mark);
        // Encode the length of the serialized mark and the data itself in a fixed-length fashion, to ease reading
        $prefix = sprintf("%6d:%6d:%s:", strlen($markSerialized), strlen($value), $markSerialized);
        $valueInternal = $prefix . $value;
        @file_put_contents($fullPath, $valueInternal, LOCK_EX);
    }

    /**
     * Retrieves a stored value from the cache, if reusable.
     *
     * @param string[] $keyPath multi-component key
     * @param mixed $mark to verify reusability
     * @return string|false string on hit, boolean false on miss
     */
    public function get($keyPath, $mark)
    {
        // prepend root path
        array_unshift($keyPath, $this->rootPath);
        $fullPath = implode('/', $keyPath);

        $cacheInfo = $this->getInternal($fullPath, $mark);
        if (!$cacheInfo) {
            return false;
        }
        try {
            $valueLength = $cacheInfo['length'];
            // NOTE: fread($stream, 0) is a php warning
            if ($valueLength > 0) {
                $value = fread($cacheInfo['stream'], $valueLength);
            } else {
                $value = '';
            }
            flock($cacheInfo['stream'], LOCK_UN);
            fclose($cacheInfo['stream']);
            return $value;
        } catch (\Exception $e) {
            flock($cacheInfo['stream'], LOCK_UN);
            fclose($cacheInfo['stream']);
            throw $e;
        }
    }

    /**
     * Sends cached data (if present + reusable) directly to the browser with the given $mimeType
     *
     * @param string[] $keyPath
     * @param mixed $mark to verify reusability
     * @param string $mimeType
     * @return bool true if data sent, false if no reusable cache item was available
     * @throws \Exception
     */
    public function emit($keyPath, $mark, $mimeType)
    {
        // prepend root path
        array_unshift($keyPath, $this->rootPath);
        $fullPath = implode('/', $keyPath);

        $cacheInfo = $this->getInternal($fullPath, $mark);
        if (!$cacheInfo) {
            return false;
        }
        try {
            header("Content-Type: $mimeType");
            header("Content-Length: {$cacheInfo['length']}");
            fpassthru($cacheInfo['stream']);
            flock($cacheInfo['stream'], LOCK_UN);
            fclose($cacheInfo['stream']);
            return true;
        } catch (\Exception $e) {
            flock($cacheInfo['stream'], LOCK_UN);
            fclose($cacheInfo['stream']);
            throw $e;
        }
    }

    /**
     * @param string $fullPath
     * @param mixed $mark
     * @return array|false
     */
    protected function getInternal($fullPath, $mark)
    {
        $stream = @fopen($fullPath, 'rb');
        if ($stream === false) {
            return false;
        }
        if (false === flock($stream, LOCK_SH)) {
            return false;
        }
        try {
            $header = fread($stream, 14);
            if ($header === false || strlen($header) != 14) {
                throw new CacheMiss();
            }
            $markSerialized = serialize($mark);
            $markLength = intval(substr($header, 0, 6));
            $valueLength = intval(substr($header, 7, 6));

            $markPrevious = fread($stream, $markLength + 1);
            if ($markPrevious === false || substr($markPrevious, 0, -1) !== $markSerialized) {
                throw new CacheMiss();
            }
            return array(
                'length' => $valueLength,
                'stream' => $stream,
            );
        } catch (CacheMiss $e) {
            flock($stream, LOCK_UN);
            fclose($stream);
            return false;
        } catch (\Exception $e) {
            // this is something severely wrong, rethrow after release lock + file
            flock($stream, LOCK_UN);
            fclose($stream);
            throw $e;
        }
    }
}
